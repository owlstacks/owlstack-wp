<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

declare(strict_types=1);

namespace Owlstack\WordPress\Database;

/**
 * Manages the delivery log database table creation and schema.
 */
class DeliveryLogTable
{
    public const TABLE_NAME = 'owlstack_delivery_logs';

    /**
     * Create the delivery logs table using dbDelta().
     */
    public static function create(): void
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned DEFAULT NULL,
            platform varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            external_id varchar(255) DEFAULT NULL,
            external_url text DEFAULT NULL,
            error text DEFAULT NULL,
            payload longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY platform (platform),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }

    /**
     * Drop the delivery logs table.
     */
    public static function drop(): void
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
    }

    /**
     * Get the full table name with prefix.
     */
    public static function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . self::TABLE_NAME;
    }
}
