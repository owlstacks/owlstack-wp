<?php

declare(strict_types=1);

namespace Owlstack\WordPress;

use Owlstack\WordPress\Database\DeliveryLogTable;

/**
 * Handles complete plugin uninstallation.
 *
 * Removes all plugin data: options, database tables, post meta,
 * transients, and capabilities.
 */
class Uninstaller
{
    /**
     * Run on plugin uninstall.
     */
    public static function uninstall(): void
    {
        self::removeOptions();
        self::removePostMeta();
        self::removeTokens();
        self::dropTables();
        self::removeCapabilities();
        self::clearTransients();
    }

    /**
     * Remove all plugin options from wp_options.
     */
    private static function removeOptions(): void
    {
        delete_option('owlstack_settings');
        delete_option('owlstack_db_version');
    }

    /**
     * Remove all post meta created by the plugin.
     */
    private static function removePostMeta(): void
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_owlstack_%'"
        );
    }

    /**
     * Remove all stored OAuth tokens.
     */
    private static function removeTokens(): void
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'owlstack_token_%'"
        );
    }

    /**
     * Drop custom database tables.
     */
    private static function dropTables(): void
    {
        DeliveryLogTable::drop();
    }

    /**
     * Remove custom capabilities from all roles.
     */
    private static function removeCapabilities(): void
    {
        $capabilities = ['manage_owlstack', 'owlstack_publish', 'owlstack_view_logs'];

        foreach (wp_roles()->roles as $roleName => $roleData) {
            $role = get_role($roleName);
            if ($role === null) {
                continue;
            }

            foreach ($capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }

    /**
     * Clear any transients created by the plugin.
     */
    private static function clearTransients(): void
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_owlstack_%' OR option_name LIKE '_transient_timeout_owlstack_%'"
        );
    }
}
