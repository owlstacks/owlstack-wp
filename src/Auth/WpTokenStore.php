<?php

declare(strict_types=1);

namespace Synglify\WordPress\Auth;

use DateTimeImmutable;
use Synglify\Core\Auth\AccessToken;
use Synglify\Core\Auth\Contracts\TokenStoreInterface;

/**
 * WordPress wp_options-based token storage with encryption.
 *
 * Stores OAuth tokens in wp_options, encrypted using wp_salt().
 */
class WpTokenStore implements TokenStoreInterface
{
    private const OPTION_PREFIX = 'synglify_token_';

    public function get(string $platform, string $accountId): ?AccessToken
    {
        $key = $this->optionKey($platform, $accountId);
        $stored = get_option($key);

        if ($stored === false || ! is_array($stored)) {
            return null;
        }

        $token = $this->decrypt($stored['token'] ?? '');
        if ($token === '') {
            return null;
        }

        $refreshToken = isset($stored['refresh_token'])
            ? $this->decrypt($stored['refresh_token'])
            : null;

        $expiresAt = isset($stored['expires_at'])
            ? new DateTimeImmutable($stored['expires_at'])
            : null;

        return new AccessToken(
            token: $token,
            refreshToken: $refreshToken !== '' ? $refreshToken : null,
            expiresAt: $expiresAt,
            scopes: $stored['scopes'] ?? [],
            metadata: $stored['metadata'] ?? [],
        );
    }

    public function store(string $platform, string $accountId, AccessToken $token): void
    {
        $key = $this->optionKey($platform, $accountId);

        $data = [
            'token'         => $this->encrypt($token->token),
            'refresh_token' => $token->refreshToken !== null ? $this->encrypt($token->refreshToken) : null,
            'expires_at'    => $token->expiresAt?->format('c'),
            'scopes'        => $token->scopes,
            'metadata'      => $token->metadata,
            'updated_at'    => (new DateTimeImmutable())->format('c'),
        ];

        update_option($key, $data, false);
    }

    public function revoke(string $platform, string $accountId): void
    {
        delete_option($this->optionKey($platform, $accountId));
    }

    public function has(string $platform, string $accountId): bool
    {
        return get_option($this->optionKey($platform, $accountId)) !== false;
    }

    // ── Encryption helpers ───────────────────────────────────────────────

    private function encrypt(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $key = $this->encryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            return base64_encode($value); // Fallback: base64 if OpenSSL unavailable.
        }

        return base64_encode($iv . $encrypted);
    }

    private function decrypt(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false || strlen($decoded) < 17) {
            return $value; // Not encrypted or too short — return as-is.
        }

        $key = $this->encryptionKey();
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return $decrypted !== false ? $decrypted : $value;
    }

    private function encryptionKey(): string
    {
        return hash('sha256', wp_salt('auth'), true);
    }

    private function optionKey(string $platform, string $accountId): string
    {
        return self::OPTION_PREFIX . $platform . '_' . sanitize_key($accountId);
    }
}
