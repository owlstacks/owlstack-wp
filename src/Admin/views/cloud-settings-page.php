<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var bool $isPaired */
/** @var ?string $revealedToken */
/** @var array{hint: string, created_at: ?int, created_by: ?int, last_used_at: ?int} $tokenInfo */
/** @var \Owlstack\WordPress\Cloud\CloudSettings $settings */

$owlstack_notice = isset($_GET['owlstack-notice']) ? sanitize_key(wp_unslash($_GET['owlstack-notice'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap owlstack-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if ($owlstack_notice === 'token-revoked'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Site token revoked. OwlStack Cloud can no longer publish to this site.', 'owlstack'); ?></p>
        </div>
    <?php elseif ($owlstack_notice === 'settings-saved'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Cloud settings saved.', 'owlstack'); ?></p>
        </div>
    <?php endif; ?>

    <p>
        <?php esc_html_e('Connect this site to OwlStack Cloud to receive posts you compose there. Generate a site token below, then paste it together with your site URL into the OwlStack dashboard when connecting WordPress. The token only allows OwlStack Cloud to create posts, upload images, and remove posts it created — nothing else.', 'owlstack'); ?>
    </p>

    <?php if (! $isPaired): ?>
        <div class="owlstack-cloud-promo notice notice-info">
            <h2 class="owlstack-cloud-promo__title">
                <?php esc_html_e('Do not have an OwlStack account yet?', 'owlstack'); ?>
            </h2>
            <p>
                <?php
                printf(
                    /* translators: %d: number of platforms OwlStack Cloud supports */
                    esc_html__('OwlStack Cloud is the hosted dashboard this token pairs with. It publishes to %d platforms, schedules ahead on a shared calendar, reports on what performed, and drafts posts for you with AI. WordPress becomes one destination among them, so a single post can go to this site and your social accounts at once.', 'owlstack'),
                    (int) \Owlstack\WordPress\Admin\CloudPromo::CLOUD_PLATFORM_COUNT,
                );
                ?>
            </p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(\Owlstack\WordPress\Admin\CloudPromo::url('/register', 'cloud-page')); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Start a free trial', 'owlstack'); ?>
                </a>
                <a class="button" href="<?php echo esc_url(\Owlstack\WordPress\Admin\CloudPromo::url('/platforms', 'cloud-page-platforms')); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('See all platforms', 'owlstack'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Site token -->
    <h2><?php esc_html_e('Site Token', 'owlstack'); ?></h2>

    <?php if (is_string($revealedToken)): ?>
        <div class="notice notice-success">
            <p><strong><?php esc_html_e('Your new site token — copy it now, it will not be shown again:', 'owlstack'); ?></strong></p>
            <p><code style="font-size:14px;user-select:all;word-break:break-all;"><?php echo esc_html($revealedToken); ?></code></p>
        </div>
    <?php endif; ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e('Status', 'owlstack'); ?></th>
            <td>
                <?php if ($isPaired): ?>
                    <span class="owlstack-badge owlstack-badge--success"><?php esc_html_e('Token active', 'owlstack'); ?></span>
                    <?php if ($tokenInfo['hint'] !== ''): ?>
                        <code><?php echo esc_html($tokenInfo['hint']); ?>…</code>
                    <?php endif; ?>
                    <?php if ($tokenInfo['created_at'] !== null): ?>
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: date the token was generated */
                                esc_html__('Generated on %s.', 'owlstack'),
                                esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $tokenInfo['created_at'])),
                            );
                            if ($tokenInfo['last_used_at'] !== null) {
                                echo ' ';
                                printf(
                                    /* translators: %s: date the token was last used */
                                    esc_html__('Last used on %s.', 'owlstack'),
                                    esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $tokenInfo['last_used_at'])),
                                );
                            }
                            ?>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="owlstack-badge owlstack-badge--pending"><?php esc_html_e('Not connected', 'owlstack'); ?></span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-right:8px;">
        <?php wp_nonce_field('owlstack_cloud_generate'); ?>
        <input type="hidden" name="action" value="owlstack_cloud_generate" />
        <?php submit_button($isPaired ? __('Regenerate Token', 'owlstack') : __('Generate Token', 'owlstack'), 'primary', 'submit', false); ?>
    </form>

    <?php if ($isPaired): ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
            <?php wp_nonce_field('owlstack_cloud_revoke'); ?>
            <input type="hidden" name="action" value="owlstack_cloud_revoke" />
            <?php submit_button(__('Revoke Token', 'owlstack'), 'delete', 'submit', false); ?>
        </form>
    <?php endif; ?>

    <?php if ($isPaired): ?>
        <p class="description" style="margin-top:12px;">
            <?php esc_html_e('Regenerating or revoking the token immediately disconnects OwlStack Cloud until the new token is saved there.', 'owlstack'); ?>
        </p>
    <?php endif; ?>

    <!-- Content handling -->
    <h2><?php esc_html_e('Incoming Content', 'owlstack'); ?></h2>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('owlstack_cloud_settings'); ?>
        <input type="hidden" name="action" value="owlstack_cloud_settings" />

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="owlstack-cloud-policy"><?php esc_html_e('Post Status', 'owlstack'); ?></label>
                </th>
                <td>
                    <select id="owlstack-cloud-policy" name="post_status_policy">
                        <option value="honor" <?php selected($settings->statusPolicy(), 'honor'); ?>>
                            <?php esc_html_e('Use the status OwlStack Cloud requests', 'owlstack'); ?>
                        </option>
                        <option value="draft" <?php selected($settings->statusPolicy(), 'draft'); ?>>
                            <?php esc_html_e('Always save as draft', 'owlstack'); ?>
                        </option>
                        <option value="publish" <?php selected($settings->statusPolicy(), 'publish'); ?>>
                            <?php esc_html_e('Always publish immediately', 'owlstack'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Choose "Always save as draft" to review every post in WordPress before it goes live.', 'owlstack'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="owlstack-cloud-author"><?php esc_html_e('Post Author', 'owlstack'); ?></label>
                </th>
                <td>
                    <?php
                    wp_dropdown_users([
                        'id'       => 'owlstack-cloud-author',
                        'name'     => 'default_author',
                        'selected' => $settings->defaultAuthor(),
                        'who'      => 'authors',
                    ]);
                    ?>
                    <p class="description"><?php esc_html_e('Posts from OwlStack Cloud are attributed to this user.', 'owlstack'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="owlstack-cloud-post-type"><?php esc_html_e('Post Type', 'owlstack'); ?></label>
                </th>
                <td>
                    <select id="owlstack-cloud-post-type" name="post_type">
                        <?php foreach (get_post_types(['public' => true], 'objects') as $owlstack_type): ?>
                            <?php if ($owlstack_type->name === 'attachment') { continue; } ?>
                            <option value="<?php echo esc_attr($owlstack_type->name); ?>" <?php selected($settings->postType(), $owlstack_type->name); ?>>
                                <?php echo esc_html($owlstack_type->labels->singular_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Cloud Settings', 'owlstack')); ?>
    </form>
</div>
