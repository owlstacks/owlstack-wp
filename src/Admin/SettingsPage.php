<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Admin;

/**
 * Registers the Owlstack admin settings page and settings fields.
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
            page_title: __('Owlstack', 'owlstack-wp'),
            menu_title: __('Owlstack', 'owlstack-wp'),
            capability: 'manage_options',
            menu_slug: 'owlstack',
            callback: [$this, 'render'],
            icon_url: 'dashicons-share',
            position: 80,
        );

        add_submenu_page(
            parent_slug: 'owlstack',
            page_title: __('Settings', 'owlstack-wp'),
            menu_title: __('Settings', 'owlstack-wp'),
            capability: 'manage_options',
            menu_slug: 'owlstack',
            callback: [$this, 'render'],
        );
    }

    /**
     * Register settings, sections, and fields via the Settings API.
     */
    public function registerSettings(): void
    {
        register_setting(
            'owlstack_settings_group',
            'owlstack_settings',
            [
                'type'              => 'array',
                'sanitize_callback' => [$this->optionsManager, 'sanitize'],
            ],
        );

        // ── Telegram section ─────────────────────────────────────────────
        add_settings_section(
            'owlstack_telegram',
            __('Telegram', 'owlstack-wp'),
            fn () => printf('<p>%s</p>', esc_html__('Configure your Telegram Bot API credentials.', 'owlstack-wp')),
            'owlstack',
        );

        $this->addField('telegram', 'api_token', __('Bot API Token', 'owlstack-wp'), true);
        $this->addField('telegram', 'bot_username', __('Bot Username', 'owlstack-wp'));
        $this->addField('telegram', 'channel_username', __('Channel Username', 'owlstack-wp'));
        $this->addField('telegram', 'channel_signature', __('Channel Signature', 'owlstack-wp'));
        $this->addSelectField('telegram', 'parse_mode', __('Parse Mode', 'owlstack-wp'), ['HTML', 'Markdown', 'MarkdownV2']);

        // ── Twitter / X section ──────────────────────────────────────────
        add_settings_section(
            'owlstack_twitter',
            __('Twitter / X', 'owlstack-wp'),
            fn () => printf('<p>%s</p>', esc_html__('Configure your Twitter (X) API credentials.', 'owlstack-wp')),
            'owlstack',
        );

        $this->addField('twitter', 'consumer_key', __('Consumer Key (API Key)', 'owlstack-wp'), true);
        $this->addField('twitter', 'consumer_secret', __('Consumer Secret', 'owlstack-wp'), true);
        $this->addField('twitter', 'access_token', __('Access Token', 'owlstack-wp'), true);
        $this->addField('twitter', 'access_token_secret', __('Access Token Secret', 'owlstack-wp'), true);

        // ── Facebook section ─────────────────────────────────────────────
        add_settings_section(
            'owlstack_facebook',
            __('Facebook', 'owlstack-wp'),
            fn () => printf('<p>%s</p>', esc_html__('Configure your Facebook Page API credentials.', 'owlstack-wp')),
            'owlstack',
        );

        $this->addField('facebook', 'app_id', __('App ID', 'owlstack-wp'), true);
        $this->addField('facebook', 'app_secret', __('App Secret', 'owlstack-wp'), true);
        $this->addField('facebook', 'page_access_token', __('Page Access Token', 'owlstack-wp'), true);
        $this->addField('facebook', 'page_id', __('Page ID', 'owlstack-wp'), true);
        $this->addField('facebook', 'default_graph_version', __('Graph API Version', 'owlstack-wp'));

        // ── Proxy section ────────────────────────────────────────────────
        add_settings_section(
            'owlstack_proxy',
            __('Proxy', 'owlstack-wp'),
            fn () => printf('<p>%s</p>', esc_html__('Configure a proxy for servers that cannot access social networks directly.', 'owlstack-wp')),
            'owlstack',
        );

        $this->addProxyField('type', __('Proxy Type', 'owlstack-wp'));
        $this->addProxyField('hostname', __('Hostname', 'owlstack-wp'));
        $this->addProxyField('port', __('Port', 'owlstack-wp'));
        $this->addProxyField('username', __('Username', 'owlstack-wp'));
        $this->addProxyField('password', __('Password', 'owlstack-wp'), true);
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
        $fieldId = "owlstack_{$platform}_{$key}";
        $section = "owlstack_{$platform}";

        add_settings_field(
            $fieldId,
            $label,
            function () use ($platform, $key, $fieldId, $isSecret): void {
                $value = $this->optionsManager->get("platforms.{$platform}.{$key}", '');
                $type = $isSecret ? 'password' : 'text';
                printf(
                    '<input type="%s" id="%s" name="owlstack_settings[platforms][%s][%s]" value="%s" class="regular-text" autocomplete="off" />',
                    esc_attr($type),
                    esc_attr($fieldId),
                    esc_attr($platform),
                    esc_attr($key),
                    esc_attr((string) $value),
                );
            },
            'owlstack',
            $section,
        );
    }

    private function addSelectField(string $platform, string $key, string $label, array $options): void
    {
        $fieldId = "owlstack_{$platform}_{$key}";
        $section = "owlstack_{$platform}";

        add_settings_field(
            $fieldId,
            $label,
            function () use ($platform, $key, $fieldId, $options): void {
                $value = $this->optionsManager->get("platforms.{$platform}.{$key}", '');
                printf('<select id="%s" name="owlstack_settings[platforms][%s][%s]">', esc_attr($fieldId), esc_attr($platform), esc_attr($key));
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
            'owlstack',
            $section,
        );
    }

    private function addProxyField(string $key, string $label, bool $isSecret = false): void
    {
        $fieldId = "owlstack_proxy_{$key}";

        add_settings_field(
            $fieldId,
            $label,
            function () use ($key, $fieldId, $isSecret): void {
                $value = $this->optionsManager->get("proxy.{$key}", '');
                $type = $isSecret ? 'password' : 'text';
                printf(
                    '<input type="%s" id="%s" name="owlstack_settings[proxy][%s]" value="%s" class="regular-text" autocomplete="off" />',
                    esc_attr($type),
                    esc_attr($fieldId),
                    esc_attr($key),
                    esc_attr((string) $value),
                );
            },
            'owlstack',
            'owlstack_proxy',
        );
    }
}
