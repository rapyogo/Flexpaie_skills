<?php

namespace FlexPay\Laravel\Services;

use FlexPay\Laravel\Support\FlexPayStatus;
use FlexPay\Laravel\Support\OperatorDetector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlexPayService
{
    protected string $merchantCode;
    protected string $apiToken;
    protected string $apiUrl;
    protected string $checkUrl;
    protected string $cardUrl;
    protected ?string $defaultCallbackUrl;

    public function __construct(?string $merchantCode = null, ?string $apiToken = null)
    {
        $this->merchantCode = $merchantCode ?? config('flexpay.merchant_code', 'SIMULATED');
        $this->apiToken     = self::normalizeToken($apiToken ?? config('flexpay.api_token', ''));
        $this->apiUrl       = rtrim(config('flexpay.api_url', 'https://backend.flexpay.cd/api/rest/v1'), '/');
        $this->checkUrl     = rtrim(config('flexpay.check_url', $this->apiUrl . '/check'), '/');
        $this->cardUrl      = rtrim(config('flexpay.card_url', $this->apiUrl . '/cardService'), '/');
        $this->defaultCallbackUrl = config('flexpay.callback_url');
    }

    /**
     * Vérifie si le service fonctionne en mode simulation (développement local).
     */
    public function isSimulated(): bool
    {
        return strtoupper(trim($this->merchantCode)) === 'SIMULATED';
    }

    /**
     * Initie un paiement Mobile Money (Airtel, Vodacom, Orange, Africell).
     *
     * @param string $phone Numéro de téléphone du client
     * @param string $reference Référence unique générée par votre application
     * @param float $amount Montant à facturer
     * @param string $currency Devise (USD ou CDF)
     * @param string|null $callbackUrl URL de notification webhook
     * @return array Résultat contenant ['success' => bool, 'orderNumber' => ?string, 'message' => string, 'raw' => array]
     */
    public function initiateMobileMoney(
        string $phone,
        string $reference,
        float $amount,
        string $currency = 'USD',
        ?string $callbackUrl = null
    ): array {
        if ($this->isSimulated()) {
            return $this->generateSimulatedInitiation($reference, $amount, $currency);
        }

        $normalizedPhone = OperatorDetector::normalize($phone);
        $url = $this->apiUrl . '/paymentService';
        $payload = [
            'merchant'    => $this->merchantCode,
            'type'        => '1', // 1 = Mobile Money
            'phone'       => $normalizedPhone,
            'reference'   => $reference,
            'amount'      => number_format($amount, 2, '.', ''),
            'currency'    => strtoupper($currency),
            'callbackUrl' => $callbackUrl ?? $this->defaultCallbackUrl,
        ];

        try {
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => $this->apiToken,
            ])
            ->timeout(30)
            ->retry(2, 500)
            ->post($url, $payload);

            $data = $response->json() ?? [];
            $code = (string) ($data['code'] ?? '-1');
            $orderNumber = $data['orderNumber'] ?? null;
            $message = $data['message'] ?? 'Erreur lors de l\'initiation FlexPay';

            Log::info('FlexPay initiateMobileMoney', [
                'reference'   => $reference,
                'status_code' => $response->status(),
                'code'        => $code,
                'orderNumber' => $orderNumber,
            ]);

            // Dans l'API FlexPay, code == "0" signifie que la demande de push a été transmise
            $isOk = $response->successful() && ($code === '0' || !empty($orderNumber));

            return [
                'success'     => $isOk,
                'orderNumber' => $orderNumber,
                'message'     => $message,
                'raw'         => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('FlexPay initiateMobileMoney Exception: ' . $e->getMessage(), [
                'reference' => $reference,
                'exception' => $e,
            ]);

            return [
                'success'     => false,
                'orderNumber' => null,
                'message'     => 'Impossible de joindre le serveur de paiement : ' . $e->getMessage(),
                'raw'         => [],
            ];
        }
    }

    /**
     * Vérifie l'état actuel d'une transaction via l'endpoint /check/{orderNumber}.
     *
     * @param string $orderNumber Identifiant retourné par FlexPay
     * @return array Résultat normalisé ['state' => string, 'is_completed' => bool, 'is_pending' => bool, 'raw' => array]
     */
    public function checkTransaction(string $orderNumber): array
    {
        if ($this->isSimulated()) {
            return $this->generateSimulatedCheck($orderNumber);
        }

        $url = $this->checkUrl . '/' . rawurlencode($orderNumber);

        try {
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => $this->apiToken,
            ])
            ->timeout(15)
            ->get($url);

            $data = $response->json() ?? [];
            $tx = $data['transaction'] ?? [];
            $statusCode = (string) ($tx['status'] ?? ($data['status'] ?? ''));

            $state = FlexPayStatus::normalizeState($statusCode);

            Log::info('FlexPay checkTransaction', [
                'orderNumber' => $orderNumber,
                'statusCode'  => $statusCode,
                'state'       => $state,
            ]);

            return [
                'success'      => $response->successful(),
                'state'        => $state,
                'is_completed' => FlexPayStatus::isCompleted($statusCode),
                'is_pending'   => FlexPayStatus::isPending($statusCode),
                'is_failed'    => FlexPayStatus::isFailed($statusCode),
                'amount'       => isset($tx['amount']) ? (float) $tx['amount'] : null,
                'channel'      => $tx['channel'] ?? null,
                'raw'          => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('FlexPay checkTransaction Exception: ' . $e->getMessage(), [
                'orderNumber' => $orderNumber,
            ]);

            return [
                'success'      => false,
                'state'        => FlexPayStatus::STATE_PENDING, // En cas de timeout temporaire, rester en attente
                'is_completed' => false,
                'is_pending'   => true,
                'is_failed'    => false,
                'amount'       => null,
                'channel'      => null,
                'raw'          => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Génère une réponse simulée d'initiation pour les tests en local.
     */
    protected function generateSimulatedInitiation(string $reference, float $amount, string $currency): array
    {
        $orderNumber = 'SIM-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10)) . '-' . time();
        return [
            'success'     => true,
            'orderNumber' => $orderNumber,
            'message'     => 'Transaction simulée (développement local)',
            'raw'         => [
                'code'        => '0',
                'message'     => 'Simulation active',
                'orderNumber' => $orderNumber,
                'amount'      => $amount,
                'currency'    => $currency,
            ],
        ];
    }

    /**
     * Génère une vérification simulée (réussite automatique après 5s).
     */
    protected function generateSimulatedCheck(string $orderNumber): array
    {
        return [
            'success'      => true,
            'state'        => FlexPayStatus::STATE_COMPLETED,
            'is_completed' => true,
            'is_pending'   => false,
            'is_failed'    => false,
            'amount'       => 10.0,
            'channel'      => 'simulated',
            'raw'          => [
                'code'        => '0',
                'transaction' => [
                    'orderNumber' => $orderNumber,
                    'status'      => '0',
                    'channel'     => 'simulated',
                ],
            ],
        ];
    }

    /**
     * Normalise le format de l'en-tête Authorization Bearer.
     */
    public static function normalizeToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') return '';
        return stripos($token, 'Bearer ') === 0 ? $token : 'Bearer ' . $token;
    }
}
