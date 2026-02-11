<?php

declare(strict_types=1);

namespace Synglify\WordPress\Database;

use Synglify\Core\Delivery\DeliveryStatus;
use Synglify\Core\Publishing\PublishResult;

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

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
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

        $where = [];
        $values = [];

        if ($args['platform'] !== '') {
            $where[] = 'platform = %s';
            $values[] = $args['platform'];
        }

        if ($args['status'] !== '') {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ($args['post_id'] > 0) {
            $where[] = 'post_id = %d';
            $values[] = $args['post_id'];
        }

        $whereClause = ! empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Validate orderby to prevent SQL injection.
        $allowedOrderby = ['id', 'post_id', 'platform', 'status', 'created_at'];
        $orderby = in_array($args['orderby'], $allowedOrderby, true) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $offset = ($args['page'] - 1) * $args['per_page'];

        // Get total count.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $countQuery = "SELECT COUNT(*) FROM {$table} {$whereClause}";
        $total = ! empty($values)
            ? (int) $wpdb->get_var($wpdb->prepare($countQuery, ...$values))
            : (int) $wpdb->get_var($countQuery);

        // Get paginated results.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = "SELECT * FROM {$table} {$whereClause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $queryValues = array_merge($values, [$args['per_page'], $offset]);
        $items = $wpdb->get_results($wpdb->prepare($query, ...$queryValues));

        return [
            'items' => $items ?: [],
            'total' => $total,
        ];
    }

    /**
     * Delete a log entry by ID.
     */
    public static function delete(int $id): bool
    {
        global $wpdb;

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
