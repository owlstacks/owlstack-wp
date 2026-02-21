<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

declare(strict_types=1);

namespace Owlstack\WordPress\Admin;

/**
 * Registers the Owlstack admin settings page and per-platform sub-pages.
 */
class SettingsPage
{
    /**
     * Platform definitions with their fields and configuration.
     *
     * @var array<string, array{label: string, description: string, fields: array}>
     */
    private const PLATFORMS = [
        'telegram' => [
            'label'       => 'Telegram',
            'description' => 'Configure your Telegram Bot API credentials.',
            'fields'      => [
                'api_token'         => ['label' => 'Bot API Token', 'secret' => true],
                'bot_username'      => ['label' => 'Bot Username'],
                'channel_username'  => ['label' => 'Channel Username'],
                'channel_signature' => ['label' => 'Channel Signature'],
                'parse_mode'        => ['label' => 'Parse Mode', 'type' => 'select', 'options' => ['HTML', 'Markdown', 'MarkdownV2']],
            ],
        ],
        'twitter' => [
            'label'       => 'Twitter / X',
            'description' => 'Configure your Twitter (X) API credentials.',
            'fields'      => [
                'consumer_key'        => ['label' => 'Consumer Key (API Key)', 'secret' => true],
                'consumer_secret'     => ['label' => 'Consumer Secret', 'secret' => true],
                'access_token'        => ['label' => 'Access Token', 'secret' => true],
                'access_token_secret' => ['label' => 'Access Token Secret', 'secret' => true],
            ],
        ],
        'facebook' => [
            'label'       => 'Facebook',
            'description' => 'Configure your Facebook Page API credentials.',
            'fields'      => [
                'app_id'                => ['label' => 'App ID', 'secret' => true],
                'app_secret'            => ['label' => 'App Secret', 'secret' => true],
                'page_access_token'     => ['label' => 'Page Access Token', 'secret' => true],
                'page_id'               => ['label' => 'Page ID', 'secret' => true],
                'default_graph_version' => ['label' => 'Graph API Version'],
            ],
        ],
        'instagram' => [
            'label'       => 'Instagram',
            'description' => 'Configure your Instagram API credentials.',
            'fields'      => [
                'access_token'         => ['label' => 'Access Token', 'secret' => true],
                'instagram_account_id' => ['label' => 'Instagram Account ID'],
            ],
        ],
        'linkedin' => [
            'label'       => 'LinkedIn',
            'description' => 'Configure your LinkedIn API credentials.',
            'fields'      => [
                'access_token'    => ['label' => 'Access Token', 'secret' => true],
                'person_id'       => ['label' => 'Person ID', 'hint' => 'For personal profiles'],
                'organization_id' => ['label' => 'Organization ID', 'hint' => 'For company pages'],
            ],
        ],
        'discord' => [
            'label'       => 'Discord',
            'description' => 'Configure your Discord credentials. Use either a webhook URL or bot token with channel ID.',
            'fields'      => [
                'webhook_url' => ['label' => 'Webhook URL', 'hint' => 'For webhook mode'],
                'bot_token'   => ['label' => 'Bot Token', 'secret' => true, 'hint' => 'For bot mode'],
                'channel_id'  => ['label' => 'Channel ID', 'hint' => 'Required for bot mode'],
            ],
        ],
        'pinterest' => [
            'label'       => 'Pinterest',
            'description' => 'Configure your Pinterest API credentials.',
            'fields'      => [
                'access_token' => ['label' => 'Access Token', 'secret' => true],
                'board_id'     => ['label' => 'Board ID'],
            ],
        ],
        'reddit' => [
            'label'       => 'Reddit',
            'description' => 'Configure your Reddit API credentials.',
            'fields'      => [
                'access_token' => ['label' => 'Access Token', 'secret' => true],
                'subreddit'    => ['label' => 'Subreddit'],
                'username'     => ['label' => 'Username'],
            ],
        ],
        'slack' => [
            'label'       => 'Slack',
            'description' => 'Configure your Slack credentials. Use either a bot token with channel or webhook URL.',
            'fields'      => [
                'bot_token'   => ['label' => 'Bot Token', 'secret' => true, 'hint' => 'For bot mode'],
                'channel'     => ['label' => 'Channel', 'hint' => 'Required for bot mode (e.g., #general)'],
                'webhook_url' => ['label' => 'Webhook URL', 'hint' => 'For webhook mode'],
            ],
        ],
        'tumblr' => [
            'label'       => 'Tumblr',
            'description' => 'Configure your Tumblr API credentials.',
            'fields'      => [
                'access_token'    => ['label' => 'Access Token', 'secret' => true],
                'blog_identifier' => ['label' => 'Blog Identifier'],
            ],
        ],
        'whatsapp' => [
            'label'       => 'WhatsApp',
            'description' => 'Configure your WhatsApp Business API credentials.',
            'fields'      => [
                'access_token'    => ['label' => 'Access Token', 'secret' => true],
                'phone_number_id' => ['label' => 'Phone Number ID'],
            ],
        ],
    ];

    public function __construct(
        private readonly OptionsManager $optionsManager,
    ) {
    }

    /**
     * Get all supported platform definitions.
     *
     * @return array<string, array{label: string, description: string, fields: array}>
     */
    public static function platforms(): array
    {
        return self::PLATFORMS;
    }

    /**
     * Register the admin menu and per-platform sub-pages.
     */
    public function register(): void
    {
        add_menu_page(
            page_title: __('Owlstack', 'owlstack'),
            menu_title: __('Owlstack', 'owlstack'),
            capability: 'manage_options',
            menu_slug: 'owlstack',
            callback: [$this, 'renderSettings'],
            icon_url: 'dashicons-share',
            position: 80,
        );

        add_submenu_page(
            parent_slug: 'owlstack',
            page_title: __('Settings', 'owlstack'),
            menu_title: __('Settings', 'owlstack'),
            capability: 'manage_options',
            menu_slug: 'owlstack',
            callback: [$this, 'renderSettings'],
        );

        foreach (self::PLATFORMS as $key => $platform) {
            add_submenu_page(
                parent_slug: 'owlstack',
                page_title: sprintf(
                    /* translators: %s: platform name */
                    __('%s Settings', 'owlstack'),
                    $platform['label'],
                ),
                menu_title: $platform['label'],
                capability: 'manage_options',
                menu_slug: "owlstack-{$key}",
                callback: fn () => $this->renderPlatform($key),
            );
        }
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

        // ── Proxy section on main settings page ──────────────────────────
        add_settings_section(
            'owlstack_proxy',
            __('Proxy', 'owlstack'),
            fn () => printf('<p>%s</p>', esc_html__('Configure a proxy for servers that cannot access social networks directly.', 'owlstack')),
            'owlstack',
        );

        $this->addProxyField('type', __('Proxy Type', 'owlstack'));
        $this->addProxyField('hostname', __('Hostname', 'owlstack'));
        $this->addProxyField('port', __('Port', 'owlstack'));
        $this->addProxyField('username', __('Username', 'owlstack'));
        $this->addProxyField('password', __('Password', 'owlstack'), true);

        // ── Per-platform sections ────────────────────────────────────────
        foreach (self::PLATFORMS as $platformKey => $platform) {
            $pageSlug  = "owlstack-{$platformKey}";
            $sectionId = "owlstack_{$platformKey}_section";

            add_settings_section(
                $sectionId,
                $platform['label'],
                fn () => printf('<p>%s</p>', esc_html__($platform['description'], 'owlstack')),
                $pageSlug,
            );

            foreach ($platform['fields'] as $fieldKey => $field) {
                $type = $field['type'] ?? 'text';
                $hint = $field['hint'] ?? null;

                if ($type === 'select') {
                    $this->addSelectField($platformKey, $fieldKey, __($field['label'], 'owlstack'), $field['options'], $pageSlug, $sectionId);
                } else {
                    $isSecret = $field['secret'] ?? false;
                    $this->addField($platformKey, $fieldKey, __($field['label'], 'owlstack'), $isSecret, $pageSlug, $sectionId, $hint);
                }
            }
        }
    }

    /**
     * Render the main settings page (proxy + overview).
     */
    public function renderSettings(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $platforms         = self::PLATFORMS;
        $optionsManager    = $this->optionsManager;
        $configuredNames   = $optionsManager->configuredPlatformNames();

        require __DIR__ . '/views/settings-page.php';
    }

    /**
     * Render an individual platform settings page.
     */
    public function renderPlatform(string $platformKey): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        if (! isset(self::PLATFORMS[$platformKey])) {
            return;
        }

        $platform       = self::PLATFORMS[$platformKey];
        $platformSlug   = $platformKey;
        $optionsManager = $this->optionsManager;

        require __DIR__ . '/views/platform-settings-page.php';
    }

    // ── Field helpers ────────────────────────────────────────────────────

    private function addField(
        string $platform,
        string $key,
        string $label,
        bool $isSecret = false,
        string $page = 'owlstack',
        string $section = '',
        ?string $hint = null,
    ): void {
        $fieldId   = "owlstack_{$platform}_{$key}";
        $sectionId = $section ?: "owlstack_{$platform}";

        add_settings_field(
            $fieldId,
            $label,
            function () use ($platform, $key, $fieldId, $isSecret, $hint): void {
                $value = $this->optionsManager->get("platforms.{$platform}.{$key}", '');
                $type  = $isSecret ? 'password' : 'text';
                printf(
                    '<input type="%s" id="%s" name="owlstack_settings[platforms][%s][%s]" value="%s" class="regular-text" autocomplete="off" />',
                    esc_attr($type),
                    esc_attr($fieldId),
                    esc_attr($platform),
                    esc_attr($key),
                    esc_attr((string) $value),
                );
                if ($hint !== null) {
                    printf('<p class="description">%s</p>', esc_html($hint));
                }
            },
            $page,
            $sectionId,
        );
    }

    private function addSelectField(
        string $platform,
        string $key,
        string $label,
        array $options,
        string $page = 'owlstack',
        string $section = '',
    ): void {
        $fieldId   = "owlstack_{$platform}_{$key}";
        $sectionId = $section ?: "owlstack_{$platform}";

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
            $page,
            $sectionId,
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
                $type  = $isSecret ? 'password' : 'text';
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
