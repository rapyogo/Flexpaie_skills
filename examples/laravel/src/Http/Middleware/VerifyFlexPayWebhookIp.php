<?php

namespace FlexPay\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de sécurité pour les webhooks entrants FlexPay.
 *
 * Vérifie que la requête provient exclusivement des serveurs officiels FlexPay :
 * - 156.0.198.27
 * - 156.0.198.19
 */
class VerifyFlexPayWebhookIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $whitelist = config('flexpay.ip_whitelist', ['156.0.198.27', '156.0.198.19']);
        $strict = config('flexpay.strict_ip', true);

        // En environnement local ou test, autoriser le contournement si configuré
        if (!$strict || app()->environment('local', 'testing')) {
            return $next($request);
        }

        // Récupération de l'adresse IP client via la chaîne TrustProxies de Laravel
        $clientIp = $request->ip();

        // Vérification avec l'en-tête X-Forwarded-For pour les architectures multi-proxy
        $forwardedIps = array_map('trim', explode(',', $request->header('x-forwarded-for', '')));
        $candidateIps = array_unique(array_filter(array_merge([$clientIp], $forwardedIps)));

        $isAuthorized = false;
        foreach ($candidateIps as $ip) {
            if (in_array($ip, $whitelist, true)) {
                $isAuthorized = true;
                break;
            }
        }

        if (!$isAuthorized) {
            Log::warning('FlexPay Webhook bloqué : IP non autorisée', [
                'client_ip'     => $clientIp,
                'forwarded_for' => $request->header('x-forwarded-for'),
                'whitelist'     => $whitelist,
                'url'           => $request->fullUrl(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Accès non autorisé : IP source non reconnue.',
            ], 403);
        }

        // Vérification optionnelle d'un token secret partagé
        $secret = config('flexpay.webhook_secret');
        if (!empty($secret)) {
            $token = $request->header('X-FlexPay-Token') ?? $request->query('token');
            if ($token !== $secret) {
                Log::warning('FlexPay Webhook bloqué : Secret token invalide');
                return response()->json([
                    'status'  => false,
                    'message' => 'Accès non autorisé : Jeton de sécurité invalide.',
                ], 403);
            }
        }

        return $next($request);
    }
}
