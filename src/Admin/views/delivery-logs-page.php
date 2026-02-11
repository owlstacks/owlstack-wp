<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var array $items */
/** @var int $total */
/** @var int $totalPages */
/** @var array $args */
?>
<div class="wrap synglify-delivery-logs">
    <h1><?php esc_html_e('Synglify Delivery Logs', 'synglify-wp'); ?></h1>

    <?php settings_errors('synglify_logs'); ?>

    <!-- Filters -->
    <div class="tablenav top">
        <form method="get" class="synglify-logs-filter">
            <input type="hidden" name="page" value="synglify-logs" />

            <select name="platform">
                <option value=""><?php esc_html_e('All Platforms', 'synglify-wp'); ?></option>
                <?php foreach (['telegram', 'twitter', 'facebook'] as $p) : ?>
                    <option value="<?php echo esc_attr($p); ?>" <?php selected($args['platform'], $p); ?>>
                        <?php echo esc_html(ucfirst($p === 'twitter' ? 'X (Twitter)' : $p)); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value=""><?php esc_html_e('All Statuses', 'synglify-wp'); ?></option>
                <?php foreach (['pending', 'publishing', 'published', 'failed'] as $s) : ?>
                    <option value="<?php echo esc_attr($s); ?>" <?php selected($args['status'], $s); ?>>
                        <?php echo esc_html(ucfirst($s)); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php submit_button(__('Filter', 'synglify-wp'), 'secondary', 'filter', false); ?>
        </form>

        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php
                printf(
                    /* translators: %s: number of items */
                    esc_html(_n('%s item', '%s items', $total, 'synglify-wp')),
                    esc_html(number_format_i18n($total)),
                );
                ?>
            </span>
            <?php if ($totalPages > 1) : ?>
                <?php
                echo paginate_links([
                    'base'      => add_query_arg('paged', '%#%'),
                    'format'    => '',
                    'current'   => $args['page'],
                    'total'     => $totalPages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ]);
                ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Log table -->
    <form method="post">
        <?php wp_nonce_field('synglify_logs_bulk', 'synglify_logs_nonce'); ?>
        <input type="hidden" name="synglify_bulk_action" value="" />

        <table class="wp-list-table widefat fixed striped synglify-logs-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all" />
                    </td>
                    <th><?php esc_html_e('Date', 'synglify-wp'); ?></th>
                    <th><?php esc_html_e('Post', 'synglify-wp'); ?></th>
                    <th><?php esc_html_e('Platform', 'synglify-wp'); ?></th>
                    <th><?php esc_html_e('Status', 'synglify-wp'); ?></th>
                    <th><?php esc_html_e('External URL', 'synglify-wp'); ?></th>
                    <th><?php esc_html_e('Error', 'synglify-wp'); ?></th>
                    <th><?php esc_html_e('Actions', 'synglify-wp'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="8"><?php esc_html_e('No delivery logs found.', 'synglify-wp'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($items as $item) : ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr((string) $item->id); ?>" />
                            </th>
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at))); ?></td>
                            <td>
                                <?php if ($item->post_id) : ?>
                                    <a href="<?php echo esc_url(get_edit_post_link((int) $item->post_id) ?? '#'); ?>">
                                        <?php echo esc_html(get_the_title((int) $item->post_id) ?: "#{$item->post_id}"); ?>
                                    </a>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="synglify-platform synglify-platform-<?php echo esc_attr($item->platform); ?>">
                                    <?php echo esc_html(ucfirst($item->platform === 'twitter' ? 'X (Twitter)' : $item->platform)); ?>
                                </span>
                            </td>
                            <td>
                                <span class="synglify-status synglify-status-<?php echo esc_attr($item->status); ?>">
                                    <?php echo esc_html(ucfirst($item->status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($item->external_url) : ?>
                                    <a href="<?php echo esc_url($item->external_url); ?>" target="_blank" rel="noopener">
                                        <?php esc_html_e('View', 'synglify-wp'); ?> &#8599;
                                    </a>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item->error) : ?>
                                    <span class="synglify-error-text" title="<?php echo esc_attr($item->error); ?>">
                                        <?php echo esc_html(mb_strimwidth($item->error, 0, 80, '...')); ?>
                                    </span>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $deleteUrl = wp_nonce_url(
                                    add_query_arg(
                                        ['action' => 'delete', 'log_id' => $item->id],
                                        admin_url('admin.php?page=synglify-logs'),
                                    ),
                                    'synglify_delete_log_' . $item->id,
                                );
                                ?>
                                <a href="<?php echo esc_url($deleteUrl); ?>" class="synglify-delete-link" onclick="return confirm('<?php esc_attr_e('Delete this log entry?', 'synglify-wp'); ?>');">
                                    <?php esc_html_e('Delete', 'synglify-wp'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <button type="submit" class="button" onclick="this.form.synglify_bulk_action.value='delete'; return confirm('<?php esc_attr_e('Delete selected entries?', 'synglify-wp'); ?>');">
                <?php esc_html_e('Delete Selected', 'synglify-wp'); ?>
            </button>
        </div>
    </form>
</div>
