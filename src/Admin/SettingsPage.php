<?php

declare(strict_types=1);

namespace Synglify\WordPress\Admin;

/**
 * Registers the Synglify admin settings page and settings fields.
 */
class SettingsPage
{
    public function __construct(
        private readonly OptionsManager $optionsManager,
    ) {
    }

    /**
     * Register the admin menu pages.
     */
    public function register(): void
    {
        add_menu_page(
            page_title: __('Synglify', 'synglify-wp'),
            menu_title: __('Synglify', 'synglify-wp'),
            capability: 'manage_options',
            menu_slug: 'synglify',
            callback: [$this, 'render'],
            icon_url: 'dashicons-share',
            position: 80,
        );

        add_submenu_page(
            parent_slug: 'synglify',
            page_title: __('Settings', 'synglify-wp'),
            menu_title: __('Settings', 'synglify-wp'),
            capability: 'manage_options',
            menu_slug: 'synglify',
            callback: [$this, 'render'],
        );
    }

    /**
     * Register settings, sections, and fields via the Settings API.
     */
    public function registerSettings(): void
    {
        register_setting(
            'synglify_settings_group',
            'synglify_settings',
            [
                'type'              => 'array',
                'sanitize_callback' => [$this->optionsManager, 'sanitize'],
            ],
        );

        // ── Telegram section ─────────────────────────────────────────────
        add_settings_section(
            'synglify_telegram',
            __('Telegram', 'synglify-wp'),
            fn () => printf('<p>%s</p>', esc_html__('Configure your Telegram Bot API credentials.', 'synglify-wp')),
            'synglify',
        );

        $this->addField('telegram', 'api_token', __('Bot API Token', 'synglify-wp'), true);
        $this->addField('telegram', 'bot_username', __('Bot Username', 'synglify-wp'));
        $this->addField('telegram', 'channel_username', __('Channel Username', 'synglify-wp'));
        $this->addField('telegram', 'channel_signature', __('Channel Signature', 'synglify-wp'));
        $this->addSelectField('telegram', 'parse_mode', __('Parse Mode', 'synglify-wp'), ['HTML', 'Markdown', 'MarkdownV2']);

        // ── Twitter / X section ──────────────────────────────────────────
        add_settings_section(
            'synglify_twitter',
            __('Twitter / X', 'synglify-wp'),
            fn () => printf('<p>%s</p>', esc_html__('Configure your Twitter (X) API credentials.', 'synglify-wp')),
            'synglify',
        );

        $this->addField('twitter', 'consumer_key', __('Consumer Key (API Key)', 'synglify-wp'), true);
        $this->addField('twitter', 'consumer_secret', __('Consumer Secret', 'synglify-wp'), true);
        $this->addField('twitter', 'access_token', __('Access Token', 'synglify-wp'), true);
        $this->addField('twitter', 'access_token_secret', __('Access Token Secret', 'synglify-wp'), true);

        // ── Facebook section ─────────────────────────────────────────────
        add_settings_section(
            'synglify_facebook',
            __('Facebook', 'synglify-wp'),
            fn () => printf('<p>%s</p>', esc_html__('Configure your Facebook Page API credentials.', 'synglify-wp')),
            'synglify',
        );

        $this->addField('facebook', 'app_id', __('App ID', 'synglify-wp'), true);
        $this->addField('facebook', 'app_secret', __('App Secret', 'synglify-wp'), true);
        $this->addField('facebook', 'page_access_token', __('Page Access Token', 'synglify-wp'), true);
        $this->addField('facebook', 'page_id', __('Page ID', 'synglify-wp'), true);
        $this->addField('facebook', 'default_graph_version', __('Graph API Version', 'synglify-wp'));

        // ── Proxy section ────────────────────────────────────────────────
        add_settings_section(
            'synglify_proxy',
            __('Proxy', 'synglify-wp'),
            fn () => printf('<p>%s</p>', esc_html__('Configure a proxy for servers that cannot access social networks directly.', 'synglify-wp')),
            'synglify',
        );

        $this->addProxyField('type', __('Proxy Type', 'synglify-wp'));
        $this->addProxyField('hostname', __('Hostname', 'synglify-wp'));
        $this->addProxyField('port', __('Port', 'synglify-wp'));
        $this->addProxyField('username', __('Username', 'synglify-wp'));
        $this->addProxyField('password', __('Password', 'synglify-wp'), true);
    }

    /**
     * Render the settings page.
     */
    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        require __DIR__ . '/views/settings-page.php';
    }

    // ── Field helpers ────────────────────────────────────────────────────

    private function addField(string $platform, string $key, string $label, bool $isSecret = false): void
    {
        $fieldId = "synglify_{$platform}_{$key}";
        $section = "synglify_{$platform}";

        add_settings_field(
            $fieldId,
            $label,
            function () use ($platform, $key, $fieldId, $isSecret): void {
                $value = $this->optionsManager->get("platforms.{$platform}.{$key}", '');
                $type = $isSecret ? 'password' : 'text';
                printf(
                    '<input type="%s" id="%s" name="synglify_settings[platforms][%s][%s]" value="%s" class="regular-text" autocomplete="off" />',
                    esc_attr($type),
                    esc_attr($fieldId),
                    esc_attr($platform),
                    esc_attr($key),
                    esc_attr((string) $value),
                );
            },
            'synglify',
            $section,
        );
    }

    private function addSelectField(string $platform, string $key, string $label, array $options): void
    {
        $fieldId = "synglify_{$platform}_{$key}";
        $section = "synglify_{$platform}";

        add_settings_field(
            $fieldId,
            $label,
            function () use ($platform, $key, $fieldId, $options): void {
                $value = $this->optionsManager->get("platforms.{$platform}.{$key}", '');
                printf('<select id="%s" name="synglify_settings[platforms][%s][%s]">', esc_attr($fieldId), esc_attr($platform), esc_attr($key));
                foreach ($options as $option) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option),
                        selected($value, $option, false),
                        esc_html($option),
                    );
                }
                echo '</select>';
            },
            'synglify',
            $section,
        );
    }

    private function addProxyField(string $key, string $label, bool $isSecret = false): void
    {
        $fieldId = "synglify_proxy_{$key}";

        add_settings_field(
            $fieldId,
            $label,
            function () use ($key, $fieldId, $isSecret): void {
                $value = $this->optionsManager->get("proxy.{$key}", '');
                $type = $isSecret ? 'password' : 'text';
                printf(
                    '<input type="%s" id="%s" name="synglify_settings[proxy][%s]" value="%s" class="regular-text" autocomplete="off" />',
                    esc_attr($type),
                    esc_attr($fieldId),
                    esc_attr($key),
                    esc_attr((string) $value),
                );
            },
            'synglify',
            'synglify_proxy',
        );
    }
}
