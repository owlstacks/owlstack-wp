<?php

declare(strict_types=1);

namespace Synglify\WordPress\Admin;

use Synglify\WordPress\Database\DeliveryLog;

/**
 * Admin page for viewing delivery logs.
 */
class DeliveryLogsPage
{
    /**
     * Register the delivery logs submenu page.
     */
    public function register(): void
    {
        add_submenu_page(
            parent_slug: 'synglify',
            page_title: __('Delivery Logs', 'synglify-wp'),
            menu_title: __('Delivery Logs', 'synglify-wp'),
            capability: 'manage_options',
            menu_slug: 'synglify-logs',
            callback: [$this, 'render'],
        );
    }

    /**
     * Render the delivery logs page.
     */
    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        // Handle bulk delete.
        if (isset($_POST['synglify_bulk_action']) && $_POST['synglify_bulk_action'] === 'delete') {
            $this->handleBulkDelete();
        }

        // Handle single delete.
        if (isset($_GET['action'], $_GET['log_id']) && $_GET['action'] === 'delete') {
            $this->handleSingleDelete();
        }

        // Build query args from filters.
        $args = [
            'per_page' => 20,
            'page'     => max(1, (int) ($_GET['paged'] ?? 1)),
            'platform' => sanitize_key($_GET['platform'] ?? ''),
            'status'   => sanitize_key($_GET['status'] ?? ''),
            'orderby'  => sanitize_key($_GET['orderby'] ?? 'created_at'),
            'order'    => sanitize_key($_GET['order'] ?? 'DESC'),
        ];

        $result = DeliveryLog::query($args);
        $items = $result['items'];
        $total = $result['total'];
        $totalPages = (int) ceil($total / $args['per_page']);

        require __DIR__ . '/views/delivery-logs-page.php';
    }

    private function handleBulkDelete(): void
    {
        if (
            ! isset($_POST['synglify_logs_nonce'])
            || ! wp_verify_nonce($_POST['synglify_logs_nonce'], 'synglify_logs_bulk')
        ) {
            return;
        }

        $ids = isset($_POST['log_ids']) && is_array($_POST['log_ids'])
            ? array_map('intval', $_POST['log_ids'])
            : [];

        foreach ($ids as $id) {
            DeliveryLog::delete($id);
        }

        add_settings_error(
            'synglify_logs',
            'bulk_deleted',
            sprintf(
                /* translators: %d: number of deleted entries */
                __('%d log entries deleted.', 'synglify-wp'),
                count($ids),
            ),
            'success',
        );
    }

    private function handleSingleDelete(): void
    {
        $logId = (int) ($_GET['log_id'] ?? 0);

        if (
            $logId <= 0
            || ! isset($_GET['_wpnonce'])
            || ! wp_verify_nonce($_GET['_wpnonce'], 'synglify_delete_log_' . $logId)
        ) {
            return;
        }

        DeliveryLog::delete($logId);

        add_settings_error(
            'synglify_logs',
            'deleted',
            __('Log entry deleted.', 'synglify-wp'),
            'success',
        );
    }
}
