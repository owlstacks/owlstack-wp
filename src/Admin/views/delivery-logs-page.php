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
<div class="wrap owlstack-delivery-logs">
    <h1><?php esc_html_e('Owlstack Delivery Logs', 'owlstack'); ?></h1>

    <?php settings_errors('owlstack_logs'); ?>

    <!-- Filters -->
    <div class="tablenav top">
        <form method="get" class="owlstack-logs-filter">
            <input type="hidden" name="page" value="owlstack-logs" />

            <select name="platform">
                <option value=""><?php esc_html_e('All Platforms', 'owlstack'); ?></option>
                <?php foreach (['telegram', 'twitter', 'facebook'] as $owlstack_p) : ?>
                    <option value="<?php echo esc_attr($owlstack_p); ?>" <?php selected($args['platform'], $owlstack_p); ?>>
                        <?php echo esc_html(ucfirst($owlstack_p === 'twitter' ? 'X (Twitter)' : $owlstack_p)); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value=""><?php esc_html_e('All Statuses', 'owlstack'); ?></option>
                <?php foreach (['pending', 'publishing', 'published', 'failed'] as $owlstack_status) : ?>
                    <option value="<?php echo esc_attr($owlstack_status); ?>" <?php selected($args['status'], $owlstack_status); ?>>
                        <?php echo esc_html(ucfirst($owlstack_status)); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php submit_button(__('Filter', 'owlstack'), 'secondary', 'filter', false); ?>
        </form>

        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php
                printf(
                    /* translators: %s: number of items */
                    esc_html(_n('%s item', '%s items', $total, 'owlstack')),
                    esc_html(number_format_i18n($total)),
                );
                ?>
            </span>
            <?php if ($totalPages > 1) : ?>
                <?php
                echo wp_kses_post((string) paginate_links([
                    'base'      => add_query_arg('paged', '%#%'),
                    'format'    => '',
                    'current'   => $args['page'],
                    'total'     => $totalPages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ]));
                ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Log table -->
    <form method="post">
        <?php wp_nonce_field('owlstack_logs_bulk', 'owlstack_logs_nonce'); ?>
        <input type="hidden" name="owlstack_bulk_action" value="" />

        <table class="wp-list-table widefat fixed striped owlstack-logs-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1" />
                    </td>
                    <th><?php esc_html_e('Date', 'owlstack'); ?></th>
                    <th><?php esc_html_e('Post', 'owlstack'); ?></th>
                    <th><?php esc_html_e('Platform', 'owlstack'); ?></th>
                    <th><?php esc_html_e('Status', 'owlstack'); ?></th>
                    <th><?php esc_html_e('External URL', 'owlstack'); ?></th>
                    <th><?php esc_html_e('Error', 'owlstack'); ?></th>
                    <th><?php esc_html_e('Actions', 'owlstack'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="8"><?php esc_html_e('No delivery logs found.', 'owlstack'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($items as $owlstack_item) : ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr((string) $owlstack_item->id); ?>" />
                            </th>
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($owlstack_item->created_at))); ?></td>
                            <td>
                                <?php if ($owlstack_item->post_id) : ?>
                                    <a href="<?php echo esc_url(get_edit_post_link((int) $owlstack_item->post_id) ?? '#'); ?>">
                                        <?php echo esc_html(get_the_title((int) $owlstack_item->post_id) ? get_the_title((int) $owlstack_item->post_id) : "#{$owlstack_item->post_id}"); ?>
                                    </a>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="owlstack-platform owlstack-platform-<?php echo esc_attr($owlstack_item->platform); ?>">
                                    <?php echo esc_html(ucfirst($owlstack_item->platform === 'twitter' ? 'X (Twitter)' : $owlstack_item->platform)); ?>
                                </span>
                            </td>
                            <td>
                                <span class="owlstack-status owlstack-status-<?php echo esc_attr($owlstack_item->status); ?>">
                                    <?php echo esc_html(ucfirst($owlstack_item->status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($owlstack_item->external_url) : ?>
                                    <a href="<?php echo esc_url($owlstack_item->external_url); ?>" target="_blank" rel="noopener">
                                        <?php esc_html_e('View', 'owlstack'); ?> &#8599;
                                    </a>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($owlstack_item->error) : ?>
                                    <span class="owlstack-error-text" title="<?php echo esc_attr($owlstack_item->error); ?>">
                                        <?php echo esc_html(mb_strimwidth($owlstack_item->error, 0, 80, '...')); ?>
                                    </span>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $owlstack_delete_url = wp_nonce_url(
                                    add_query_arg(
                                        ['action' => 'delete', 'log_id' => $owlstack_item->id],
                                        admin_url('admin.php?page=owlstack-logs'),
                                    ),
                                    'owlstack_delete_log_' . $owlstack_item->id,
                                );
                                ?>
                                <a href="<?php echo esc_url($owlstack_delete_url); ?>" class="owlstack-delete-link" onclick="return confirm('<?php esc_attr_e('Delete this log entry?', 'owlstack'); ?>');">
                                    <?php esc_html_e('Delete', 'owlstack'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <button type="submit" class="button" onclick="this.form.owlstack_bulk_action.value='delete'; return confirm('<?php esc_attr_e('Delete selected entries?', 'owlstack'); ?>');">
                <?php esc_html_e('Delete Selected', 'owlstack'); ?>
            </button>
        </div>
    </form>
</div>
