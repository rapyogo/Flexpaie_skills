<?php

namespace FlexPay\Laravel\Http\Controllers;

use FlexPay\Laravel\Events\FlexPayPaymentCompleted;
use FlexPay\Laravel\Events\FlexPayPaymentFailed;
use FlexPay\Laravel\Events\FlexPayPaymentInitiated;
use FlexPay\Laravel\Http\Requests\FlexPayPaymentRequest;
use FlexPay\Laravel\Models\FlexPayPayment;
use FlexPay\Laravel\Services\FlexPayService;
use FlexPay\Laravel\Support\FlexPayStatus;
use FlexPay\Laravel\Support\OperatorDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class FlexPayPaymentController extends Controller
{
    public function __construct(protected FlexPayService $flexPayService)
    {
    }

    /**
     * Initie un paiement Mobile Money.
     */
    public function initiate(FlexPayPaymentRequest $request): JsonResponse
    {
        $phone     = $request->input('phone');
        $amount    = (float) $request->input('amount');
        $currency  = strtoupper($request->input('currency', config('flexpay.default_currency', 'USD')));
        $reference = $request->input('reference') ?? 'FP-' . strtoupper(Str::random(8)) . '-' . time();
        $metadata  = $request->input('metadata', []);

        $operator = OperatorDetector::detect($phone);

        // 1. Enregistrement local préalable en statut 'pending'
        $payment = FlexPayPayment::create([
            'reference' => $reference,
            'phone'     => OperatorDetector::normalize($phone),
            'operator'  => $operator,
            'amount'    => $amount,
            'currency'  => $currency,
            'channel'   => 'mobile_money',
            'status'    => FlexPayStatus::STATE_PENDING,
            'metadata'  => $metadata,
        ]);

        // 2. Appel du service FlexPay
        $result = $this->flexPayService->initiateMobileMoney($phone, $reference, $amount, $currency);

        if (!$result['success'] || empty($result['orderNumber'])) {
            $payment->markAsFailed($result['message'], $result['raw']);
            event(new FlexPayPaymentFailed($payment, $result['message']));

            return response()->json([
                'status'  => false,
                'message' => $result['message'],
            ], 422);
        }

        // 3. Mise à jour avec le numéro d'ordre FlexPay
        $payment->update([
            'order_number'     => $result['orderNumber'],
            'response_payload' => $result['raw'],
        ]);

        event(new FlexPayPaymentInitiated($payment));

        return response()->json([
            'status'      => true,
            'message'     => 'Demande de paiement envoyée. Veuillez valider sur votre téléphone.',
            'orderNumber' => $result['orderNumber'],
            'reference'   => $reference,
            'operator'    => $operator,
            'operator_name' => OperatorDetector::getServiceName($operator),
            'currency'    => $currency,
            'amount'      => $amount,
        ]);
    }

    /**
     * Endpoint de polling : vérifie l'état de la transaction.
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $orderNumber = $request->input('orderNumber');
        $reference   = $request->input('reference');

        if (!$orderNumber && !$reference) {
            return response()->json(['status' => 'error', 'message' => 'Identifiant de commande manquant.'], 400);
        }

        // Recherche locale
        $payment = FlexPayPayment::query()
            ->when($orderNumber, fn($q) => $q->where('order_number', $orderNumber))
            ->when(!$orderNumber && $reference, fn($q) => $q->where('reference', $reference))
            ->first();

        // Si déjà finalisé localement (ex: par le webhook), retourner immédiatement sans appeler l'API
        if ($payment && $payment->status === FlexPayStatus::STATE_COMPLETED) {
            return response()->json([
                'status'  => 'completed',
                'message' => 'Paiement confirmé avec succès.',
            ]);
        }

        if ($payment && $payment->status === FlexPayStatus::STATE_FAILED) {
            return response()->json([
                'status'  => 'failed',
                'message' => $payment->error_message ?? 'Le paiement a échoué.',
            ]);
        }

        // Interrogation de l'API FlexPay
        $targetOrderNumber = $orderNumber ?? $payment?->order_number;
        if (!$targetOrderNumber) {
            return response()->json(['status' => 'pending', 'message' => 'Numéro de commande en attente.']);
        }

        $check = $this->flexPayService->checkTransaction($targetOrderNumber);

        // GESTION CRITIQUE DU STATUT :
        // Si is_completed (statut '0') -> validation
        if ($check['is_completed']) {
            if ($payment) {
                $payment->markAsCompleted($check['channel'] ?? 'mobile_money', $check['raw']);
                event(new FlexPayPaymentCompleted($payment, $check['raw']));
            }
            return response()->json([
                'status'  => 'completed',
                'message' => 'Paiement confirmé avec succès.',
            ]);
        }

        // Si is_pending (statut '2') -> CONTINUER LE POLLING (ne surtout pas abandonner !)
        if ($check['is_pending'] || $check['state'] === FlexPayStatus::STATE_PENDING) {
            return response()->json([
                'status'  => 'pending',
                'message' => 'En attente de saisie de votre code PIN sur votre mobile...',
            ]);
        }

        // Si is_failed (statut '1') -> échec réel
        if ($check['is_failed']) {
            if ($payment) {
                $payment->markAsFailed('Transaction refusée par l\'opérateur', $check['raw']);
                event(new FlexPayPaymentFailed($payment, 'Transaction refusée par l\'opérateur'));
            }
            return response()->json([
                'status'  => 'failed',
                'message' => 'Le paiement a été refusé ou a expiré.',
            ]);
        }

        return response()->json([
            'status'  => 'pending',
            'message' => 'Traitement en cours...',
        ]);
    }
}
