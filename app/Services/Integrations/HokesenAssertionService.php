<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Cache;
use JsonException;

class HokesenAssertionService
{
    /**
     * Parse and validate an assertion JWT from hokesen.dev.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidHokesenAssertionException
     */
    public function validate(string $token): array
    {
        if (! config('integrations.hokesen.enabled', false)) {
            throw new InvalidHokesenAssertionException('Integration is disabled', 'disabled');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $this->splitToken($token);

        $header = $this->decodeJsonSegment($encodedHeader, 'invalid_header');
        $payload = $this->decodeJsonSegment($encodedPayload, 'invalid_payload');

        $this->ensureSupportedAlgorithm($header);
        $this->ensureValidSignature($encodedHeader.'.'.$encodedPayload, $encodedSignature);

        $claims = $this->validateClaims($payload);
        $this->guardReplayAttack($claims['jti'], $claims['exp']);

        return $claims;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function splitToken(string $token): array
    {
        $parts = explode('.', trim($token));

        if (count($parts) !== 3) {
            throw new InvalidHokesenAssertionException('Malformed token', 'malformed');
        }

        return [$parts[0], $parts[1], $parts[2]];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonSegment(string $segment, string $reason): array
    {
        $decoded = $this->base64UrlDecode($segment);
        if ($decoded === false) {
            throw new InvalidHokesenAssertionException('Malformed token segment', $reason);
        }

        try {
            $json = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidHokesenAssertionException('Malformed JSON in token segment', $reason);
        }

        if (! is_array($json)) {
            throw new InvalidHokesenAssertionException('Token segment must decode to object', $reason);
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $header
     */
    private function ensureSupportedAlgorithm(array $header): void
    {
        $alg = $header['alg'] ?? null;

        if (! is_string($alg) || $alg !== 'HS256') {
            throw new InvalidHokesenAssertionException('Unsupported assertion algorithm', 'unsupported_alg');
        }
    }

    private function ensureValidSignature(string $signingInput, string $encodedSignature): void
    {
        $providedSignature = $this->base64UrlDecode($encodedSignature);
        if ($providedSignature === false) {
            throw new InvalidHokesenAssertionException('Invalid signature encoding', 'invalid_signature');
        }

        foreach ($this->sharedSecrets() as $secret) {
            $expected = hash_hmac('sha256', $signingInput, $secret, true);

            if (hash_equals($expected, $providedSignature)) {
                return;
            }
        }

        throw new InvalidHokesenAssertionException('Invalid signature', 'invalid_signature');
    }

    /**
     * @param  array<string, mixed>  $claims
     * @return array{
     *     iss: string,
     *     aud: string|array<int, string>,
     *     sub: mixed,
     *     email: string,
     *     email_verified: bool,
     *     exp: int,
     *     iat: int,
     *     nbf: int|null,
     *     jti: string
     * }
     */
    private function validateClaims(array $claims): array
    {
        $this->requireClaim($claims, 'iss');
        $this->requireClaim($claims, 'aud');
        $this->requireClaim($claims, 'email');
        $this->requireClaim($claims, 'email_verified');
        $this->requireClaim($claims, 'exp');
        $this->requireClaim($claims, 'iat');
        $this->requireClaim($claims, 'jti');

        $issuer = config('integrations.hokesen.issuer');
        if (! is_string($claims['iss']) || $claims['iss'] !== $issuer) {
            throw new InvalidHokesenAssertionException('Invalid issuer', 'invalid_issuer');
        }

        $audience = config('integrations.hokesen.audience');
        if (! $this->audienceMatches($claims['aud'], $audience)) {
            throw new InvalidHokesenAssertionException('Invalid audience', 'invalid_audience');
        }

        if (! is_string($claims['email']) || ! filter_var($claims['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidHokesenAssertionException('Invalid email claim', 'invalid_email');
        }

        if ($claims['email_verified'] !== true) {
            throw new InvalidHokesenAssertionException('Email must be verified on issuer side', 'email_not_verified');
        }

        $exp = filter_var($claims['exp'], FILTER_VALIDATE_INT);
        $iat = filter_var($claims['iat'], FILTER_VALIDATE_INT);
        $nbf = array_key_exists('nbf', $claims) ? filter_var($claims['nbf'], FILTER_VALIDATE_INT) : null;

        if ($exp === false || $iat === false || (array_key_exists('nbf', $claims) && $nbf === false)) {
            throw new InvalidHokesenAssertionException('Invalid temporal claims', 'invalid_time_claim');
        }

        $now = now()->timestamp;
        $clockSkew = (int) config('integrations.hokesen.clock_skew_seconds', 60);
        $maxTtl = (int) config('integrations.hokesen.max_ttl_seconds', 300);

        if ($exp < $now - $clockSkew) {
            throw new InvalidHokesenAssertionException('Assertion expired', 'expired');
        }

        if ($iat > $now + $clockSkew) {
            throw new InvalidHokesenAssertionException('Assertion issued in the future', 'issued_in_future');
        }

        if ($nbf !== null && $nbf > $now + $clockSkew) {
            throw new InvalidHokesenAssertionException('Assertion not active yet', 'not_before');
        }

        if (($exp - $iat) > $maxTtl) {
            throw new InvalidHokesenAssertionException('Assertion TTL exceeds maximum', 'ttl_too_long');
        }

        $jti = $claims['jti'];
        if (! is_string($jti) || trim($jti) === '' || strlen($jti) > 128) {
            throw new InvalidHokesenAssertionException('Invalid jti', 'invalid_jti');
        }

        return [
            'iss' => $claims['iss'],
            'aud' => $claims['aud'],
            'sub' => $claims['sub'] ?? null,
            'email' => strtolower(trim($claims['email'])),
            'email_verified' => true,
            'exp' => $exp,
            'iat' => $iat,
            'nbf' => $nbf,
            'jti' => $jti,
        ];
    }

    /**
     * @param  string|array<int, string>  $audClaim
     */
    private function audienceMatches(mixed $audClaim, string $expectedAudience): bool
    {
        if (! is_string($expectedAudience) || $expectedAudience === '') {
            return false;
        }

        if (is_string($audClaim)) {
            return hash_equals($expectedAudience, $audClaim);
        }

        if (! is_array($audClaim)) {
            return false;
        }

        return in_array($expectedAudience, $audClaim, true);
    }

    private function guardReplayAttack(string $jti, int $exp): void
    {
        $clockSkew = (int) config('integrations.hokesen.clock_skew_seconds', 60);
        $ttl = max(1, ($exp - now()->timestamp) + $clockSkew);

        $cacheKey = 'integrations:hokesen:jti:'.sha1($jti);
        if (! Cache::add($cacheKey, true, now()->addSeconds($ttl))) {
            throw new InvalidHokesenAssertionException('Assertion replay detected', 'replay');
        }
    }

    /**
     * @return array<int, string>
     */
    private function sharedSecrets(): array
    {
        $secrets = array_filter([
            config('integrations.hokesen.shared_secret'),
            config('integrations.hokesen.previous_shared_secret'),
        ], fn (mixed $value) => is_string($value) && trim($value) !== '');

        if ($secrets === []) {
            throw new InvalidHokesenAssertionException('Shared secret is not configured', 'misconfigured');
        }

        return array_values($secrets);
    }

    private function requireClaim(array $claims, string $claim): void
    {
        if (! array_key_exists($claim, $claims)) {
            throw new InvalidHokesenAssertionException("Missing required claim: {$claim}", 'missing_claim');
        }
    }

    private function base64UrlDecode(string $value): string|false
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
