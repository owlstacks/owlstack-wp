<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var array{label: string, description: string, docs_url: string, fields: array} $platform */
/** @var string $platformSlug */
/** @var \Owlstack\WordPress\Admin\OptionsManager $optionsManager */
?>
<div class="wrap owlstack-settings owlstack-platform-settings">
    <h1>
        <?php
        printf(
            /* translators: %s: platform name */
            esc_html__('Owlstack — %s Settings', 'owlstack'),
            esc_html($platform['label']),
        );
        ?>
    </h1>

    <?php
    if (! empty($_GET['settings-updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        add_settings_error('owlstack_settings', 'owlstack_updated', __('Settings saved.', 'owlstack'), 'updated');
    }
    settings_errors('owlstack_settings');
    ?>

    <div class="owlstack-platform-docs-notice" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 12px 16px; margin: 16px 0;">
        <p style="margin: 0;">
            <?php
            printf(
                /* translators: %1$s: platform name, %2$s: opening link tag, %3$s: closing link tag */
                esc_html__('Need help setting up %1$s? See the %2$sconfiguration guide%3$s for step-by-step instructions on obtaining API credentials and configuring this platform for your WordPress site.', 'owlstack'),
                esc_html($platform['label']),
                '<a href="' . esc_url($platform['docs_url']) . '" target="_blank" rel="noopener noreferrer">',
                '</a>',
            );
            ?>
        </p>
    </div>

    <form method="post" action="options.php">
        <?php
        settings_fields('owlstack_settings_group');
        do_settings_sections("owlstack-{$platformSlug}");
        submit_button(__('Save Settings', 'owlstack'));
        ?>
    </form>

    <hr />

    <!-- ── Testing Section ────────────────────────────────────────────── -->
    <h2><?php esc_html_e('Testing', 'owlstack'); ?></h2>
    <p><?php esc_html_e('Save your settings first, then use the actions below to verify your integration.', 'owlstack'); ?></p>

    <table class="wp-list-table widefat fixed striped owlstack-test-actions-table">
        <thead>
            <tr>
                <th class="owlstack-test-col-type"><?php esc_html_e('Test', 'owlstack'); ?></th>
                <th class="owlstack-test-col-desc"><?php esc_html_e('Description', 'owlstack'); ?></th>
                <th class="owlstack-test-col-action"><?php esc_html_e('Action', 'owlstack'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- Connection Test -->
            <tr>
                <td><strong><?php esc_html_e('Connection', 'owlstack'); ?></strong></td>
                <td><?php esc_html_e('Validates that your API credentials are correct and the platform is reachable.', 'owlstack'); ?></td>
                <td>
                    <button type="button" class="button owlstack-test-btn" data-platform="<?php echo esc_attr($platformSlug); ?>">
                        <?php esc_html_e('Test Connection', 'owlstack'); ?>
                    </button>
                    <span class="spinner"></span>
                </td>
            </tr>

            <!-- Text Message Test -->
            <tr>
                <td><strong><?php esc_html_e('Text Message', 'owlstack'); ?></strong></td>
                <td><?php esc_html_e('Sends a sample text post to verify publishing works end-to-end. The message will include your site name and a timestamp.', 'owlstack'); ?></td>
                <td>
                    <button type="button" class="button owlstack-test-message-btn" data-platform="<?php echo esc_attr($platformSlug); ?>" data-type="text">
                        <?php esc_html_e('Send Test Message', 'owlstack'); ?>
                    </button>
                    <span class="spinner"></span>
                </td>
            </tr>

            <!-- Image / Media (Guidance) -->
            <tr>
                <td><strong><?php esc_html_e('Image / Media', 'owlstack'); ?></strong></td>
                <td>
                    <?php esc_html_e('To test image or media publishing:', 'owlstack'); ?>
                    <ol class="owlstack-test-steps">
                        <li><?php esc_html_e('Create a new post in WordPress and add an image via the Featured Image or content editor.', 'owlstack'); ?></li>
                        <li>
                            <?php
                            printf(
                                /* translators: %s: platform name */
                                esc_html__('In the Owlstack meta box on the post editor, select "%s" as a target platform.', 'owlstack'),
                                esc_html($platform['label']),
                            );
                            ?>
                        </li>
                        <li><?php esc_html_e('Click "Publish Now" in the meta box to send the post with its media attachments.', 'owlstack'); ?></li>
                    </ol>
                </td>
                <td>
                    <a href="<?php echo esc_url(admin_url('post-new.php')); ?>" class="button">
                        <?php esc_html_e('Create Test Post', 'owlstack'); ?>
                    </a>
                </td>
            </tr>

            <!-- Video (Guidance) -->
            <tr>
                <td><strong><?php esc_html_e('Video', 'owlstack'); ?></strong></td>
                <td>
                    <?php esc_html_e('To test video publishing, follow the same steps as image testing above, but upload a video file instead. Supported formats depend on the platform (typically MP4).', 'owlstack'); ?>
                </td>
                <td>
                    <a href="<?php echo esc_url(admin_url('post-new.php')); ?>" class="button">
                        <?php esc_html_e('Create Test Post', 'owlstack'); ?>
                    </a>
                </td>
            </tr>
        </tbody>
    </table>

    <div id="owlstack-test-result" class="owlstack-test-result" style="margin-top: 12px;"></div>

    <p style="margin-top: 24px;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=owlstack')); ?>">&larr; <?php esc_html_e('Back to Settings Overview', 'owlstack'); ?></a>
    </p>
</div>
