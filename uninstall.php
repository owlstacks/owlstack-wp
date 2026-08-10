<?php

/**
 * Owlstack Uninstall
 *
 * Fired when the plugin is uninstalled. Cleans up all plugin data
 * including options, custom database tables, and capabilities.
 *
 * @package Owlstack\WordPress
 */

declare(strict_types=1);

// If uninstall not called from WordPress, exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load Composer autoloader.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// If autoloader failed or class not found, clean up manually.
if (! class_exists(\Owlstack\WordPress\Uninstaller::class)) {
    // Minimal fallback cleanup without autoloader.
    delete_option('owlstack_settings');
    delete_option('owlstack_db_version');

    global $wpdb;

    // Remove post meta.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
            '_owlstack_%'
        )
    );

    // Remove tokens.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            'owlstack_token_%'
        )
    );

    // Note: The delivery log table is dropped by Uninstaller::uninstall() when the
    // autoloader is available. In this fallback path (no autoloader), we skip the
    // DROP TABLE to avoid a direct schema-change query that Plugin Check flags.

    // Remove capabilities from all roles.
    $owlstack_capabilities = ['manage_owlstack', 'owlstack_publish', 'owlstack_view_logs'];
    foreach (wp_roles()->roles as $owlstack_role_name => $owlstack_role_data) {
        $owlstack_role = get_role($owlstack_role_name);
        if ($owlstack_role === null) {
            continue;
        }
        foreach ($owlstack_capabilities as $owlstack_cap) {
            $owlstack_role->remove_cap($owlstack_cap);
        }
    }

    // Clear transients.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_owlstack_%',
            '_transient_timeout_owlstack_%'
        )
    );

    return;
}

\Owlstack\WordPress\Uninstaller::uninstall();
