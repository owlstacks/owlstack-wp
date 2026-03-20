<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Admin;

defined( 'ABSPATH' ) || exit;

use Owlstack\Core\Config\OwlstackConfig;

/**
 * Manages Owlstack plugin settings stored in wp_options.
 *
 * Provides a bridge between WordPress options and OwlstackConfig.
 */
class OptionsManager
{
    private const OPTION_KEY = 'owlstack_settings';

    /**
     * Build a OwlstackConfig from stored WordPress options.
     */
    public function buildConfig(): OwlstackConfig
    {
        $settings = $this->all();
        $platforms = $settings['platforms'] ?? [];

        // Filter out platforms with empty required credentials.
        $configured = [];
        foreach ($platforms as $name => $credentials) {
            if ($this->hasRequiredCredentials($name, $credentials)) {
                $configured[$name] = $credentials;
            }
        }

        return new OwlstackConfig(
            platforms: $configured,
            options: [
                'proxy' => $settings['proxy'] ?? [],
            ],
        );
    }

    /**
     * Get all plugin settings.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $settings = get_option(self::OPTION_KEY, []);

        return is_array($settings) ? $settings : [];
    }

    /**
     * Get a specific setting by dot-notation key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        $keys = explode('.', $key);

        $value = $settings;
        foreach ($keys as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Update a specific setting by dot-notation key.
     */
    public function set(string $key, mixed $value): void
    {
        $settings = $this->all();
        $keys = explode('.', $key);
        $current = &$settings;

        foreach (array_slice($keys, 0, -1) as $segment) {
            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current[end($keys)] = $value;
        $this->save($settings);
    }

    /**
     * Save all settings.
     *
     * @param array<string, mixed> $settings
     */
    public function save(array $settings): void
    {
        update_option(self::OPTION_KEY, $settings);
    }

    /**
     * Get platform-specific settings.
     *
     * @return array<string, mixed>
     */
    public function platformSettings(string $platform): array
    {
        return $this->get("platforms.{$platform}", []);
    }

    /**
     * Get proxy settings.
     *
     * @return array<string, mixed>
     */
    public function proxySettings(): array
    {
        return $this->get('proxy', []);
    }

    /**
     * Get all configured platform names (those with required credentials).
     *
     * @return string[]
     */
    public function configuredPlatformNames(): array
    {
        $platforms = $this->get('platforms', []);
        $names = [];

        foreach ($platforms as $name => $credentials) {
            if ($this->hasRequiredCredentials($name, $credentials)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Sanitize settings input from the admin form.
     *
     * Merges incoming data with existing settings so that saving one
     * platform page does not wipe credentials from other platforms.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitize(mixed $input): array
    {
        // WordPress passes $_POST data directly; guard against non-array input.
        if (! is_array($input)) {
            return $this->all();
        }

        $existing  = $this->all();
        $sanitized = [
            'platforms' => $existing['platforms'] ?? [],
            'proxy'     => $existing['proxy'] ?? [],
        ];

        // Sanitize platform credentials (merge per-platform).
        if (isset($input['platforms']) && is_array($input['platforms'])) {
            foreach ($input['platforms'] as $platform => $credentials) {
                $platform = sanitize_key($platform);
                if (is_array($credentials)) {
                    $sanitized['platforms'][$platform] = [];
                    foreach ($credentials as $key => $value) {
                        $value = sanitize_text_field((string) $value);

                        // Auto-prepend @ for username fields that require it.
                        if ($this->isUsernameField($platform, $key) && $value !== '' && strpos($value, '@') !== 0) {
                            $value = '@' . $value;
                        }

                        $sanitized['platforms'][$platform][sanitize_key($key)] = $value;
                    }
                }
            }
        }

        // Sanitize proxy settings.
        if (isset($input['proxy']) && is_array($input['proxy'])) {
            $sanitized['proxy'] = [];
            foreach ($input['proxy'] as $key => $value) {
                $sanitized['proxy'][sanitize_key($key)] = sanitize_text_field((string) $value);
            }
        }

        return $sanitized;
    }

    /**
     * Determine if a platform has its minimum required credentials configured.
     */
    private function hasRequiredCredentials(string $platform, array $credentials): bool
    {
        $requiredKeys = match ($platform) {
            'telegram'  => ['api_token'],
            'twitter'   => ['consumer_key', 'consumer_secret', 'access_token', 'access_token_secret'],
            'facebook'  => ['app_id', 'app_secret', 'page_access_token', 'page_id'],
            'instagram' => ['access_token', 'instagram_account_id'],
            'linkedin'  => ['access_token'],
            'discord'   => [],  // webhook_url OR (bot_token + channel_id)
            'pinterest' => ['access_token', 'board_id'],
            'reddit'    => ['access_token', 'subreddit'],
            'slack'     => [],  // bot_token + channel OR webhook_url
            'tumblr'    => ['access_token', 'blog_identifier'],
            'whatsapp'  => ['access_token', 'phone_number_id'],
            default     => [],
        };

        foreach ($requiredKeys as $key) {
            if (empty($credentials[$key])) {
                return false;
            }
        }

        // Discord: needs webhook_url OR (bot_token + channel_id).
        if ($platform === 'discord') {
            $hasWebhook = ! empty($credentials['webhook_url']);
            $hasBot     = ! empty($credentials['bot_token']) && ! empty($credentials['channel_id']);

            return $hasWebhook || $hasBot;
        }

        // Slack: needs (bot_token + channel) OR webhook_url.
        if ($platform === 'slack') {
            $hasBot     = ! empty($credentials['bot_token']) && ! empty($credentials['channel']);
            $hasWebhook = ! empty($credentials['webhook_url']);

            return $hasBot || $hasWebhook;
        }

        return true;
    }

    /**
     * Check if a platform field is a username that requires an @ prefix.
     */
    private function isUsernameField(string $platform, string $key): bool
    {
        $usernameFields = [
            'telegram' => ['bot_username', 'channel_username'],
        ];

        return isset($usernameFields[$platform]) && in_array($key, $usernameFields[$platform], true);
    }
}
