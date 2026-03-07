<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Database;

defined( 'ABSPATH' ) || exit;

use Owlstack\Core\Delivery\DeliveryStatus;
use Owlstack\Core\Publishing\PublishResult;

/**
 * Repository for CRUD operations on the delivery log table.
 */
class DeliveryLog
{
    /**
     * Insert a delivery log entry from a PublishResult.
     */
    public static function createFromResult(PublishResult $result, ?int $postId = null): int
    {
        global $wpdb;

        $status = $result->success ? DeliveryStatus::Published->value : DeliveryStatus::Failed->value;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            DeliveryLogTable::tableName(),
            [
                'post_id'      => $postId,
                'platform'     => $result->platformName,
                'status'       => $status,
                'external_id'  => $result->externalId,
                'external_url' => $result->externalUrl,
                'error'        => $result->error,
                'created_at'   => $result->timestamp->format('Y-m-d H:i:s'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s'],
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * Get a single log entry by ID.
     *
     * @return object|null
     */
    public static function find(int $id): ?object
    {
        global $wpdb;

        $table = DeliveryLogTable::tableName();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id )
        );
    }

    /**
     * Get log entries with pagination and optional filters.
     *
     * @param array $args {
     *     @type int    $per_page  Items per page. Default 20.
     *     @type int    $page      Current page number. Default 1.
     *     @type string $platform  Filter by platform name.
     *     @type string $status    Filter by delivery status.
     *     @type int    $post_id   Filter by post ID.
     *     @type string $orderby   Column to order by. Default 'created_at'.
     *     @type string $order     ASC or DESC. Default 'DESC'.
     * }
     * @return array{items: array, total: int}
     */
    public static function query(array $args = []): array
    {
        global $wpdb;

        $table = DeliveryLogTable::tableName();

        $defaults = [
            'per_page' => 20,
            'page'     => 1,
            'platform' => '',
            'status'   => '',
            'post_id'  => 0,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        ];

        $args = array_merge($defaults, $args);

        // Validate orderby to prevent SQL injection.
        $allowedOrderby = ['id', 'post_id', 'platform', 'status', 'created_at'];
        $orderby = in_array($args['orderby'], $allowedOrderby, true) ? $args['orderby'] : 'created_at';

        $offset = ($args['page'] - 1) * $args['per_page'];

        // Use "always-true" conditions pattern so the SQL string is fully literal (no variable interpolation).
        // When a filter is empty/zero the condition evaluates to TRUE and has no filtering effect.
        if ( strtoupper( $args['order'] ) === 'ASC' ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $total = (int) $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT COUNT(*) FROM %i WHERE (%s = %s OR platform = %s) AND (%s = %s OR status = %s) AND (%d = 0 OR post_id = %d)',
                    $table,
                    $args['platform'], '', $args['platform'],
                    $args['status'], '', $args['status'],
                    $args['post_id'], $args['post_id']
                )
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM %i WHERE (%s = %s OR platform = %s) AND (%s = %s OR status = %s) AND (%d = 0 OR post_id = %d) ORDER BY %i ASC LIMIT %d OFFSET %d',
                    $table,
                    $args['platform'], '', $args['platform'],
                    $args['status'], '', $args['status'],
                    $args['post_id'], $args['post_id'],
                    $orderby,
                    $args['per_page'],
                    $offset
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $total = (int) $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT COUNT(*) FROM %i WHERE (%s = %s OR platform = %s) AND (%s = %s OR status = %s) AND (%d = 0 OR post_id = %d)',
                    $table,
                    $args['platform'], '', $args['platform'],
                    $args['status'], '', $args['status'],
                    $args['post_id'], $args['post_id']
                )
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM %i WHERE (%s = %s OR platform = %s) AND (%s = %s OR status = %s) AND (%d = 0 OR post_id = %d) ORDER BY %i DESC LIMIT %d OFFSET %d',
                    $table,
                    $args['platform'], '', $args['platform'],
                    $args['status'], '', $args['status'],
                    $args['post_id'], $args['post_id'],
                    $orderby,
                    $args['per_page'],
                    $offset
                )
            );
        }

        return [
            'items' => $items ? $items : [],
            'total' => $total,
        ];
    }

    /**
     * Delete a log entry by ID.
     */
    public static function delete(int $id): bool
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $deleted = $wpdb->delete(
            DeliveryLogTable::tableName(),
            ['id' => $id],
            ['%d'],
        );

        return $deleted !== false;
    }

    /**
     * Delete all log entries for a specific post.
     */
    public static function deleteByPostId(int $postId): int
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return (int) $wpdb->delete(
            DeliveryLogTable::tableName(),
            ['post_id' => $postId],
            ['%d'],
        );
    }

    /**
     * Get the logs for a specific post.
     *
     * @return array<object>
     */
    public static function forPost(int $postId): array
    {
        return self::query(['post_id' => $postId, 'per_page' => 100])['items'];
    }
}
