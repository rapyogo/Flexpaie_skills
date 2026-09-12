<?php

namespace FlexPay\Laravel\Support;

/**
 * Détecteur d'opérateurs Mobile Money en République Démocratique du Congo (RDC).
 *
 * Supporte :
 * - Airtel Money : 097, 098, 099 (+24397, +24398, +24399)
 * - Vodacom M-Pesa : 081, 082, 083 (+24381, +24382, +24383)
 * - Orange Money : 080, 084, 085, 089 (+24380, +24384, +24385, +24389)
 * - Africell AfriMoney : 090, 091 (+24390, +24391)
 */
class OperatorDetector
{
    public const AIRTEL   = 'airtel';
    public const VODACOM  = 'vodacom';
    public const ORANGE   = 'orange';
    public const AFRICELL = 'africell';

    private const PREFIX_MAP = [
        '97' => self::AIRTEL,
        '98' => self::AIRTEL,
        '99' => self::AIRTEL,

        '81' => self::VODACOM,
        '82' => self::VODACOM,
        '83' => self::VODACOM,

        '80' => self::ORANGE,
        '84' => self::ORANGE,
        '85' => self::ORANGE,
        '89' => self::ORANGE,

        '90' => self::AFRICELL,
        '91' => self::AFRICELL,
    ];

    /**
     * Détecte l'opérateur télécom à partir du numéro de téléphone.
     */
    public static function detect(string $phone): ?string
    {
        $normalized = self::normalize($phone);
        if (strlen($normalized) !== 12 || !str_starts_with($normalized, '243')) {
            return null;
        }

        $prefix2 = substr($normalized, 3, 2);
        return self::PREFIX_MAP[$prefix2] ?? null;
    }

    /**
     * Normalise le numéro au format international compact standard FlexPay : 243xxxxxxxxx.
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '243' . substr($digits, 1);
        } elseif (!str_starts_with($digits, '243') && strlen($digits) === 9) {
            $digits = '243' . $digits;
        }

        return $digits;
    }

    /**
     * Vérifie si le numéro est un numéro valide de RDC avec préfixe opérateur reconnu.
     */
    public static function isValidDrcPhone(string $phone): bool
    {
        $normalized = self::normalize($phone);
        return strlen($normalized) === 12 && self::detect($normalized) !== null;
    }

    /**
     * Retourne le nom commercial du service Mobile Money.
     */
    public static function getServiceName(?string $operator): string
    {
        return match ($operator) {
            self::AIRTEL   => 'Airtel Money',
            self::VODACOM  => 'M-Pesa (Vodacom)',
            self::ORANGE   => 'Orange Money',
            self::AFRICELL => 'AfriMoney (Africell)',
            default        => 'Mobile Money',
        };
    }

    /**
     * Retourne la couleur hexadécimale associée à la marque opérateur.
     */
    public static function getBrandColor(?string $operator): string
    {
        return match ($operator) {
            self::AIRTEL   => '#E60000',
            self::VODACOM  => '#E60000',
            self::ORANGE   => '#FF7900',
            self::AFRICELL => '#682C91',
            default        => '#4F46E5',
        };
    }
}
