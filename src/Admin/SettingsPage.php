<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Admin;

defined( 'ABSPATH' ) || exit;

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
                'bot_username'      => ['label' => 'Bot Username', 'placeholder' => '@YourBot', 'hint' => 'Must start with @'],
                'channel_username'  => ['label' => 'Channel Username', 'placeholder' => '@your_channel', 'hint' => 'Must start with @'],
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
            icon_url: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0ibm9uZSIgdmlld0JveD0iLTIuNSAtMC4yNSAxNSAxNSI+PGcgZmlsbD0iI2E3YWFhZCIgY2xpcC1wYXRoPSJ1cmwoI2EpIj48cGF0aCBkPSJNOC44IDUuNHYuMmwuMS42di42bC0uMyAxLjQtLjQuNVY5SDhMOCA5bC0uMS4xaC0uMXYuMWwtLjIuMS0uMS4xLS4zLjItLjIuMS0uNi4ySDQuOGwtLjcuMS0uMy4yLS4yLjItLjIuMS0uMi4xLS4xLjEtLjEuMS0uMi4xLS4xLjEtLjIuMS4xLS4xLjEtLjEuMS0uMi4xLS4xLjEtLjJoLjF2LS4xbC4xLS4yVjEwaC4xdi0uMmwuMS0uMXYtLjRoLjFWNy45bC0uMi0uN1Y3TDMgNi45di0uMmwtLjItLjItLjItLjJ2LS4xaC0uMS44TDQgNmwuMy0uMmguMVY2bC4yLjN2LjFsLjIuNFY3TDUgN2wuMS0uMnYtLjFsLjItLjMuMi0uNmguMUw2IDZsLjguM2guNmwuMy0uMS4zLS4xLjItLjEuMi0uMXpNMCAwaDIuMmwuMS4xdi4xaC4xdi4xaC4xdi4xbC4xLjFoLjF2LjFsLjIuMXYuMUwzIDFsLjIuMnYuMWguMXYuMWwuMi4yaC4xdi4xbC4yLjJINFYyTDQgMmwuMS4xLjMuM3YtLjJMNCAydi0uMUw0IDEuNmwtLjEtLjEtLjItLjQtLjItLjJoM2wtLjEuMXYuMWwtLjIuMXYuM2wtLjIuMXYuMmwtLjQuNmguMXYtLjFsLjEtLjFMNiAydi0uMWguMWwuMS0uMmguMWwuMS0uMmguMXYtLjFoLjF2LS4xaC4xVjFIN1YxTDcgLjhWLjdoLjFsLjEtLjEuMS0uMVYuNGwuMi0uMmguMVYuMWwuMS0uMUgxMGgtLjFsLS4yLjItLjIuMS0uNS40aC0uMWwtLjcuNkg4bC0uMS4yaC0uMXYuMWgtLjFsLS4yLjFWMmgtLjFsLS41LjUtLjIuMS0uMi4xLS4yLjItLjMuM0g2bC0uMi4yLS4yLjItLjMuMmgtLjFMNSA0aC0uMWwtLjEtLjJoLS4xbC0uMS0uMS0uMS0uMWgtLjF2LS4xTDMuNyAzbC0uMS0uMS0uNS0uNC0uMy0uM2gtLjJWMmgtLjFWMkwyIDEuNkgybC0uMS0uMS0uNS0uNC0uMi0uMkwuOC42LjUuNS41LjMuMy4yLjEgMHoiLz48cGF0aCBkPSJtMSA1LjYuMi4xIDEgLjZ2LjFsLjEuMS4xLjEuMS4yaC4xbC4xLjMuMi40QTMgMyAwIDAgMSAzIDkuNHYuMmwtLjEuMXYuMWwtLjEuMS0uMS4yLS4yLjMtLjEuMmgtLjF2LjFoLS4xdi4xSDJ2LjFIMmwtLjIuMi0uOC41di02TTUgMTMuOEg4bC4yLjIuMi4yLjEuMXYuMWguMWwuMS4xLjEuMXYuMUg5di4xbC4xLjF2LjFIMWwuMS0uMS4xLS4xLjItLjJ2LS4xbC4yLS4xLjEtLjEuMi0uMi4xLS4ySDJsLjEtLjFINU00LjQgMTIuMUg4di4yaC4xdi4xaC4xbC4xLjEuMS4xLjEuMS4yLjJ2LjFoLjF2LjFsLjIuMXYuMWguMUgxdi0uMWguMVYxM2guMVYxM2guMXYtLjFoLjF2LS4xaC4xbC4xLS4yaC4xdi0uMWwuMi0uMXYtLjFsLjEtLjFoMi40TTUuMiAxMC41SDh2LjFoLjF2LjFoLjF2LjFoLjF2LjFoLjF2LjFoLjF2LjFoLjF2LjFoLjF2LjFoLjF2LjFIOXYuMUg5di4xaC4xLTYuNS4xbC4yLS4ySDNsLjItLjJoLjFsLjEtLjEuMS0uMS4yLS4xLjEtLjFINHYtLjFsLjMtLjJoMU0xLjUgMS44aC4xTDIgMmwuMi4yLS4yLjItLjEuMS0uMi40di43bC40LjkuMS4xLjIuMWguMXYuMWwuNy4yaC4ybC43LS4zLjEuMS4yLjUtMSAuNEgzcS0uNSAwLS44LS4zSDJsLS4yLS4xLS40LS40LS4xLS4yLS4xLS4xLS4yLS40LS4xLTF2LS41SDF2LS4ybC4yLS4yVjJsLjEtLjF2LS4xek04LjUgMS44bC4yLjIuMi40di4xbC4xLjIuMS41LS4xIDF2LjFsLS40LjYtLjEuMXYuMWgtLjFsLS41LjN2LjFoLS4xbC0uNi4yaC0uNGwtLjktLjItLjEtLjFoLS4xVjVsLjItLjN2LS4xaC4ybC42LjNIN3EuNCAwIDEtLjRsLjEtLjF2LS4xaC4xdi0uMXEuMy0uNC4zLS43VjNsLS4xLS41LS4xLS4xLS4zLS4zSDhWMmwuMi0uMS4xLS4xeiIvPjxwYXRoIGQ9Im0yLjQgMi41LjQuMy0uMi4zdi4yaC0uMXYuM2wuMi4yLjMuMmguNHYtLjFsLjMtLjRoLjFsLjIuMi4xLjFWNGwtLjIuMnYuMWgtLjFsLS4yLjJoLS4xYTEgMSAwIDAgMS0uOSAwTDIuMiA0IDIuMiA0IDIgMy41cTAtLjQuMi0uN2wuMS0uMnpNNy42IDIuNWwuMi4yLjIuNXYuNmgtLjFsLS4yLjMtLjEuMmgtLjFsLS4yLjFoLS44bC0uMy0uMi0uMy0uNC4zLS4yLjEtLjEuMi4zLjMuMmguM2wuMi0uMmguMXYtLjFsLjEtLjRWM2wtLjMtLjNoLjF2LS4xbC4yLS4yTTQuNSA0LjFsLjEuMWguMWwuMy4zLjMtLjNoLjN2LjFsLS4yLjQtLjEuNC0uMS4ydi4xbC0uMS4xdi4ySDV2LS4xbC0uMS0uMXYtLjFsLS4yLS4zVjVsLS4xLS4zLS4yLS4zdi0uMnpNOCAxMC42ek0uMi4yIi8+PC9nPjxkZWZzPjxjbGlwUGF0aCBpZD0iYSI+PHBhdGggZmlsbD0iI2ZmZiIgZD0iTTAgMGgxMHYxNUgweiIvPjwvY2xpcFBhdGg+PC9kZWZzPjwvc3ZnPg==',
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
                fn () => printf('<p>%s</p>', esc_html($platform['description'])),
                $pageSlug,
            );

            foreach ($platform['fields'] as $fieldKey => $field) {
                $type        = $field['type'] ?? 'text';
                $hint        = $field['hint'] ?? null;
                $placeholder = $field['placeholder'] ?? null;

                if ($type === 'select') {
                    $this->addSelectField($platformKey, $fieldKey, $field['label'], $field['options'], $pageSlug, $sectionId);
                } else {
                    $isSecret = $field['secret'] ?? false;
                    $this->addField($platformKey, $fieldKey, $field['label'], $isSecret, $pageSlug, $sectionId, $hint, $placeholder);
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
        ?string $placeholder = null,
    ): void {
        $fieldId   = "owlstack_{$platform}_{$key}";
        $sectionId = $section !== '' ? $section : "owlstack_{$platform}";

        add_settings_field(
            $fieldId,
            $label,
            function () use ($platform, $key, $fieldId, $isSecret, $hint, $placeholder): void {
                $value = $this->optionsManager->get("platforms.{$platform}.{$key}", '');
                $type  = $isSecret ? 'password' : 'text';
                printf(
                    '<input type="%s" id="%s" name="owlstack_settings[platforms][%s][%s]" value="%s" class="regular-text" autocomplete="off" placeholder="%s" />',
                    esc_attr($type),
                    esc_attr($fieldId),
                    esc_attr($platform),
                    esc_attr($key),
                    esc_attr((string) $value),
                    esc_attr((string) ($placeholder ?? '')),
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
        $sectionId = $section !== '' ? $section : "owlstack_{$platform}";

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
