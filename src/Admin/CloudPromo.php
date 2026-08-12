<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Explains OwlStack Cloud on the plugin's own admin screens.
 *
 * Stays inside the plugin's pages: no site-wide notices, and the Settings
 * card is dismissible per user.
 */
class CloudPromo
{
    public const SITE_URL = 'https://owlstack.app';

    /** Platforms reachable through Cloud, against the 11 this plugin publishes to directly. */
    public const CLOUD_PLATFORM_COUNT = 31;

    private const DISMISS_META = 'owlstack_cloud_promo_dismissed';

    private const DISMISS_ACTION = 'owlstack_dismiss_cloud_promo';

    /**
     * Build a tagged link to the marketing site so signups can be attributed.
     */
    public static function url(string $path = '/', string $placement = 'settings-card'): string
    {
        return add_query_arg(
            [
                'utm_source'   => 'wordpress-plugin',
                'utm_medium'   => 'plugin-admin',
                'utm_campaign' => 'cloud-connect',
                'utm_content'  => $placement,
            ],
            self::SITE_URL . $path,
        );
    }

    /**
     * Has the current user dismissed the Settings card?
     */
    public static function isDismissed(?int $userId = null): bool
    {
        $userId ??= get_current_user_id();

        if ($userId <= 0) {
            return false;
        }

        return (bool) get_user_meta($userId, self::DISMISS_META, true);
    }

    /**
     * Nonce-protected dismissal link.
     */
    public static function dismissUrl(): string
    {
        return wp_nonce_url(
            admin_url('admin-post.php?action=' . self::DISMISS_ACTION),
            self::DISMISS_ACTION,
        );
    }

    /**
     * Register the dismissal handler.
     */
    public static function registerActions(): void
    {
        add_action('admin_post_' . self::DISMISS_ACTION, [self::class, 'handleDismiss']);
    }

    /**
     * Persist the dismissal for the current user and return to Settings.
     */
    public static function handleDismiss(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do that.', 'owlstack'));
        }

        check_admin_referer(self::DISMISS_ACTION);

        $userId = get_current_user_id();

        if ($userId > 0) {
            update_user_meta($userId, self::DISMISS_META, '1');
        }

        wp_safe_redirect(admin_url('admin.php?page=owlstack'));
        exit;
    }
}
