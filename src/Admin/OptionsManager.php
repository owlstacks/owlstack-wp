<?php

declare(strict_types=1);

namespace Synglify\WordPress\Admin;

use Synglify\Core\Config\SynglifyConfig;

/**
 * Manages Synglify plugin settings stored in wp_options.
 *
 * Provides a bridge between WordPress options and SynglifyConfig.
 */
class OptionsManager
{
    private const OPTION_KEY = 'synglify_settings';

    /**
     * Build a SynglifyConfig from stored WordPress options.
     */
    public function buildConfig(): SynglifyConfig
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

        return new SynglifyConfig(
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
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitize(array $input): array
    {
        $sanitized = [];

        // Sanitize platform credentials.
        if (isset($input['platforms']) && is_array($input['platforms'])) {
            foreach ($input['platforms'] as $platform => $credentials) {
                $platform = sanitize_key($platform);
                if (is_array($credentials)) {
                    foreach ($credentials as $key => $value) {
                        $sanitized['platforms'][$platform][sanitize_key($key)] = sanitize_text_field((string) $value);
                    }
                }
            }
        }

        // Sanitize proxy settings.
        if (isset($input['proxy']) && is_array($input['proxy'])) {
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
            'telegram' => ['api_token'],
            'twitter'  => ['consumer_key', 'consumer_secret', 'access_token', 'access_token_secret'],
            'facebook' => ['app_id', 'app_secret', 'page_access_token', 'page_id'],
            default    => [],
        };

        foreach ($requiredKeys as $key) {
            if (empty($credentials[$key])) {
                return false;
            }
        }

        return true;
    }
}
