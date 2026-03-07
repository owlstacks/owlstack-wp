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

    // Drop delivery log table.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        $wpdb->prepare('DROP TABLE IF EXISTS %i', $wpdb->prefix . 'owlstack_delivery_logs')
    );

    // Remove capabilities from all roles.
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
