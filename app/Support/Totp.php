<?php

namespace App\Support;

/**
 * TOTP (RFC 6238) : codes à 6 chiffres qui changent toutes les 30 secondes, compatibles
 * avec Google Authenticator, Microsoft Authenticator et similaires.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function code(string $secret, ?int $time = null, int $digits = 6): string
    {
        $time ??= time();
        $counter = intdiv($time, 30);
        $hash = hash_hmac('sha1', pack('J', $counter), self::base32Decode($secret), true);

        $offset = ord($hash[19]) & 0x0F;
        $number = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($number % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    // Accepte le code actuel et une fenêtre de ±$window pas de 30 secondes (décalage d'horloge)
    public static function verify(string $secret, string $code, int $window = 1, ?int $time = null): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $time ??= time();

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, $time + $i * 30), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function uri(string $secret, string $account): string
    {
        return 'otpauth://totp/' . rawurlencode('LajanAnsanm:' . $account)
            . '?secret=' . $secret . '&issuer=LajanAnsanm';
    }

    private static function base32Encode(string $binary): string
    {
        $bits = '';
        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    private static function base32Decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret))) as $char) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
