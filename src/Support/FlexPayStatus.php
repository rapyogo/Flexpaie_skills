<?php

namespace FlexPay\Laravel\Support;

/**
 * Gestion canonique des statuts de transaction FlexPay.
 *
 * ATTENTION - PIÈGE MAJEUR DOCUMENTÉ :
 * L'API FlexPay retourne :
 * - "0" : Succès (paiement validé par le client et l'opérateur)
 * - "1" : Échec / Transaction rejetée
 * - "2" : EN ATTENTE ("Le paiement est en attente" - en attente du PIN client sur son mobile)
 *
 * Traiter "2" comme un échec ou interrompre le polling au premier "2" est la cause
 * numéro 1 des blocages télécoms en RDC : l'utilisateur clique à répétition,
 * et le switch USSD de l'opérateur verrouille son numéro pendant 15 minutes (900 secondes).
 */
class FlexPayStatus
{
    public const COMPLETED = '0';
    public const FAILED    = '1';
    public const PENDING   = '2';

    // Alias textuels internes pour la persistance en base
    public const STATE_PENDING   = 'pending';
    public const STATE_COMPLETED = 'completed';
    public const STATE_FAILED    = 'failed';
    public const STATE_CANCELLED = 'cancelled';

    /**
     * Vérifie si le statut API correspond à une transaction réussie.
     */
    public static function isCompleted(string|int|null $code): bool
    {
        $c = (string) $code;
        return $c === self::COMPLETED || $c === 'completed' || $c === 'success';
    }

    /**
     * Vérifie si le statut API correspond à une transaction toujours en attente (attente de PIN).
     */
    public static function isPending(string|int|null $code): bool
    {
        $c = (string) $code;
        return $c === self::PENDING || $c === 'pending' || $c === 'waiting';
    }

    /**
     * Vérifie si le statut API correspond à un échec définitif.
     */
    public static function isFailed(string|int|null $code): bool
    {
        $c = (string) $code;
        return $c === self::FAILED || in_array($c, ['failed', 'declined', 'error', 'cancelled'], true);
    }

    /**
     * Convertit le code API brut en état textuel Eloquent normalisé.
     */
    public static function normalizeState(string|int|null $code): string
    {
        if (self::isCompleted($code)) {
            return self::STATE_COMPLETED;
        }
        if (self::isPending($code)) {
            return self::STATE_PENDING;
        }
        return self::STATE_FAILED;
    }

    /**
     * Retourne un libellé clair pour l'interface utilisateur.
     */
    public static function label(string $state): string
    {
        return match ($state) {
            self::STATE_COMPLETED => 'Paiement effectué avec succès',
            self::STATE_PENDING   => 'En attente de confirmation sur votre téléphone...',
            self::STATE_FAILED    => 'Paiement échoué ou annulé',
            self::STATE_CANCELLED => 'Paiement annulé',
            default               => 'État inconnu',
        };
    }
}
