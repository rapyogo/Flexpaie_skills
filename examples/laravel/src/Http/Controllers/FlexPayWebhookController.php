<?php

namespace FlexPay\Laravel\Http\Controllers;

use FlexPay\Laravel\Events\FlexPayPaymentCompleted;
use FlexPay\Laravel\Events\FlexPayPaymentFailed;
use FlexPay\Laravel\Models\FlexPayPayment;
use FlexPay\Laravel\Support\FlexPayStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class FlexPayWebhookController extends Controller
{
    /**
     * Traite les notifications asynchrones (callbacks) de FlexPay.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('FlexPay Webhook reçu', [
            'ip'      => $request->ip(),
            'payload' => $payload,
        ]);

        $orderNumber = $payload['orderNumber'] ?? null;
        $reference   = $payload['reference'] ?? null;
        $status      = (string) ($payload['status'] ?? ($payload['code'] ?? ''));
        $amount      = isset($payload['amount']) ? (float) $payload['amount'] : null;
        $channel     = $payload['channel'] ?? 'mobile_money';

        if (!$orderNumber && !$reference) {
            return response()->json(['status' => false, 'message' => 'Référence ou numéro de commande manquant'], 400);
        }

        $payment = FlexPayPayment::query()
            ->when($orderNumber, fn($q) => $q->where('order_number', $orderNumber))
            ->when(!$orderNumber && $reference, fn($q) => $q->where('reference', $reference))
            ->first();

        if (!$payment) {
            Log::warning('FlexPay Webhook : Transaction non trouvée', ['orderNumber' => $orderNumber, 'reference' => $reference]);
            return response()->json(['status' => false, 'message' => 'Transaction inconnue'], 404);
        }

        // Si la transaction est déjà marquée comme complétée (idempotence)
        if ($payment->status === FlexPayStatus::STATE_COMPLETED) {
            return response()->json(['status' => true, 'message' => 'Déjà traitée'], 200);
        }

        // Validation du statut : Seul '0' correspond au succès
        if (FlexPayStatus::isCompleted($status)) {
            // Sécurité montant : Vérifier la correspondance du montant (tolérance 0.01)
            if ($amount !== null && abs($amount - (float) $payment->amount) > 0.01) {
                Log::error('FlexPay Webhook : Divergence de montant !', [
                    'attendu' => $payment->amount,
                    'reçu'    => $amount,
                ]);
                $payment->markAsFailed("Divergence de montant reçu : $amount vs {$payment->amount}", $payload);
                return response()->json(['status' => false, 'message' => 'Montant incohérent'], 422);
            }

            $success = $payment->markAsCompleted($channel, $payload);
            if ($success) {
                event(new FlexPayPaymentCompleted($payment, $payload));
            }

            return response()->json(['status' => true, 'message' => 'Paiement validé avec succès'], 200);
        }

        if (FlexPayStatus::isFailed($status)) {
            $payment->markAsFailed("Échec notifié par webhook (code: $status)", $payload);
            event(new FlexPayPaymentFailed($payment, "Code statut: $status"));
            return response()->json(['status' => true, 'message' => 'Échec enregistré'], 200);
        }

        // Si statut en attente (code '2')
        return response()->json(['status' => true, 'message' => 'En attente de confirmation'], 200);
    }
}
