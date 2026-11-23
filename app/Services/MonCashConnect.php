<?php

namespace App\Services;

use App\Exceptions\MonCashConnectException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Client MonCashConnect (passerelle MonCash indépendante, non affiliée à Digicel).
 * Contrat : https://moncashconnect.com/docs — montant entier en HTG, referenceId unique par projet, en-tête Origin
 * obligatoire (domaine présent dans « Allowed domains »), webhooks signés HMAC-SHA256 (X-MCC-Signature[-V2] + X-MCC-Timestamp).
 */
class MonCashConnect
{
    public const MIN_AMOUNT = 1;
    public const DEFAULT_MAX_AMOUNT = 1000000;   // plafond annoncé par la passerelle ; ajustable (MONCASHCONNECT_MAX_AMOUNT)
    public const SIGNATURE_TOLERANCE = 300;   // secondes (la doc demande de rejeter au-delà de 5 min)

    public function enabled(): bool
    {
        return (bool) config('services.moncashconnect.enabled')
            && filled(config('services.moncashconnect.secret'))
            && filled(config('services.moncashconnect.webhook_secret'))
            && filled($this->origin());
    }

    // Clés sk_test_proj_… : bac à sable, aucun vrai argent ne bouge
    public function sandbox(): bool
    {
        return str_starts_with((string) config('services.moncashconnect.secret'), 'sk_test_');
    }

    public function maxAmount(): int
    {
        return max(self::MIN_AMOUNT, (int) config('services.moncashconnect.max_amount', self::DEFAULT_MAX_AMOUNT));
    }

    // Un appel serveur n'envoie pas d'Origin tout seul : la passerelle exige celui de notre domaine (https://…)
    public function origin(): ?string
    {
        $origin = config('services.moncashconnect.origin') ?: config('app.url');
        $parts = parse_url((string) $origin);

        return isset($parts['scheme'], $parts['host']) ? $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '') : null;
    }

    // La passerelle n'accepte que des montants entiers dans cette fourchette
    public function canCharge(?float $amount): bool
    {
        return $this->enabled()
            && $amount !== null
            && floor($amount) === $amount
            && $amount >= self::MIN_AMOUNT
            && $amount <= $this->maxAmount();
    }

    /** @return array{paymentUrl: string, reference: ?string, expiresAt: ?string} */
    public function createPayment(string $referenceId, int $amount, string $returnUrl, ?string $name = null, ?string $email = null): array
    {
        $body = array_filter([
            'amount' => $amount,
            'referenceId' => $referenceId,
            'returnUrl' => $returnUrl,
            'customerName' => $name,
            'customerEmail' => $email,
        ], fn ($v) => $v !== null && $v !== '');

        // Idempotency-Key : un nouvel essai réseau ne peut pas créer un doublon
        $res = $this->request()->withHeaders(['Idempotency-Key' => $referenceId])->post('/pay-create', $body);

        if (! $res->successful()) {
            throw new MonCashConnectException('pay-create failed: HTTP '.$res->status().' '.($res->json('code') ?? $res->json('error') ?? ''));
        }

        $url = (string) $res->json('paymentUrl');

        // On ne redirige le client que vers une page HTTPS
        if (! str_starts_with($url, 'https://')) {
            throw new MonCashConnectException('pay-create returned an invalid paymentUrl');
        }

        return ['paymentUrl' => $url, 'reference' => $res->json('reference'), 'expiresAt' => $res->json('expiresAt')];
    }

    /** Statut faisant foi : pending | completed | failed | cancelled (+ amount en HTG) */
    public function status(string $referenceId): array
    {
        $res = $this->request()->get('/pay-status', ['reference' => $referenceId, 'referenceId' => $referenceId]);

        if (! $res->successful()) {
            throw new MonCashConnectException('pay-status failed: HTTP '.$res->status());
        }

        return (array) $res->json();
    }

    /**
     * Vérifie une notification : HMAC-SHA256 du corps BRUT. La V2 (timestamp inclus dans la signature) est préférée
     * car elle détecte un rejeu ; la V1 n'est acceptée que si la V2 est absente.
     */
    public function verifySignature(string $rawBody, ?string $v2, ?string $v1, ?string $timestamp): bool
    {
        $secret = (string) config('services.moncashconnect.webhook_secret');

        if ($secret === '' || $timestamp === null || ! ctype_digit($timestamp)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::SIGNATURE_TOLERANCE) {
            return false;
        }

        $hex = fn (?string $s) => strtolower(preg_replace('/^sha256=/i', '', trim((string) $s)));

        if (filled($v2)) {
            return hash_equals(hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret), $hex($v2));
        }

        if (filled($v1)) {
            return hash_equals(hash_hmac('sha256', $rawBody, $secret), $hex($v1));
        }

        return false;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.moncashconnect.base_url'), '/'))
            ->withToken((string) config('services.moncashconnect.secret'))
            ->withHeaders(['Origin' => (string) $this->origin()])
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }
}
