<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FlexPay Merchant & Credentials
    |--------------------------------------------------------------------------
    |
    | Définissez votre code marchand et votre jeton d'API FlexPay.
    | En local ou en test, utilisez 'SIMULATED' pour simuler les transactions
    | sans contacter les passerelles télécom réelles.
    |
    */
    'merchant_code' => env('FLEXPAY_MERCHANT_CODE', 'SIMULATED'),
    'api_token'     => env('FLEXPAY_API_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Endpoints de l'API REST v1 FlexPay
    |--------------------------------------------------------------------------
    */
    'api_url'      => env('FLEXPAY_API_URL', 'https://backend.flexpay.cd/api/rest/v1'),
    'check_url'    => env('FLEXPAY_CHECK_URL', 'https://backend.flexpay.cd/api/rest/v1/check'),
    'card_url'     => env('FLEXPAY_CARD_URL', 'https://backend.flexpay.cd/api/rest/v1/cardService'),
    'callback_url' => env('FLEXPAY_CALLBACK_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Sécurité & Whitelisting IP Webhook
    |--------------------------------------------------------------------------
    |
    | Adresses IP officielles communiquées par les équipes d'infrastructure FlexPay :
    | - 156.0.198.27 (serveur backend.flexpay.cd)
    | - 156.0.198.19 (serveur secondaire / passerelle de callback)
    |
    | Le middleware VerifyFlexPayWebhookIp s'appuie sur la configuration
    | TrustProxies de Laravel pour déterminer l'IP réelle du client.
    |
    */
    'ip_whitelist' => array_filter(array_map('trim', explode(',', env('FLEXPAY_IP_WHITELIST', '156.0.198.27,156.0.198.19')))),
    'strict_ip'    => env('FLEXPAY_STRICT_IP', true),
    'webhook_secret' => env('FLEXPAY_WEBHOOK_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Paramètres de Polling Mobile Money (Expérience Utilisateur RDC)
    |--------------------------------------------------------------------------
    |
    | Sur les réseaux USSD RDC (Airtel, Vodacom M-Pesa, Orange, Africell),
    | l'utilisateur prend en moyenne 15 à 45 secondes pour déverrouiller
    | son téléphone et saisir son code secret. Le polling doit couvrir jusqu'à
    | 180 secondes (3 minutes) pour éviter toute annulation prématurée.
    |
    */
    'polling' => [
        'interval_seconds' => 3,
        'max_attempts'     => 60, // 60 * 3s = 180s
        'timeout_seconds'  => 180,
    ],

    /*
    |--------------------------------------------------------------------------
    | Devises & Base de Données
    |--------------------------------------------------------------------------
    */
    'default_currency'     => env('FLEXPAY_DEFAULT_CURRENCY', 'USD'),
    'supported_currencies' => ['USD', 'CDF'],
    'table_name'           => env('FLEXPAY_TABLE_NAME', 'flexpay_payments'),

    /*
    |--------------------------------------------------------------------------
    | Configuration des Routes Prédéfinies
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled'    => env('FLEXPAY_ENABLE_ROUTES', true),
        'prefix'     => env('FLEXPAY_ROUTE_PREFIX', 'payments/flexpay'),
        'middleware' => ['web'],
    ],

];
