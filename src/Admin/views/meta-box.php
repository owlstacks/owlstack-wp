<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var string[] $configuredPlatforms */
/** @var string[] $selectedPlatforms */
/** @var bool $autoPublish */
/** @var WP_Post $post */

wp_nonce_field('owlstack_meta_box', 'owlstack_meta_box_nonce');

$owlstack_platform_labels = \Owlstack\WordPress\Admin\SettingsPage::platforms();
?>

<div class="owlstack-meta-box">
    <?php if (empty($configuredPlatforms)) : ?>
        <p class="owlstack-no-platforms">
            <?php
            printf(
                /* translators: %s: link open tag, %s: link close tag */
                esc_html__('No platforms configured. %1$sConfigure platforms%2$s in Owlstack settings.', 'owlstack-wp'),
                '<a href="' . esc_url(admin_url('admin.php?page=owlstack')) . '">',
                '</a>',
            );
            ?>
        </p>
    <?php else : ?>
        <p class="owlstack-meta-label"><strong><?php esc_html_e('Publish to:', 'owlstack-wp'); ?></strong></p>

        <table class="owlstack-platform-list">
            <tbody>
            <?php foreach ($configuredPlatforms as $owlstack_platform) :
                $owlstack_label = $owlstack_platform_labels[$owlstack_platform]['label'] ?? ucfirst($owlstack_platform);
            ?>
                <tr class="owlstack-platform-row" data-platform="<?php echo esc_attr($owlstack_platform); ?>">
                    <td class="owlstack-platform-checkbox-col">
                        <input
                            type="checkbox"
                            name="owlstack_platforms[]"
                            value="<?php echo esc_attr($owlstack_platform); ?>"
                            <?php checked(in_array($owlstack_platform, $selectedPlatforms, true)); ?>
                        />
                    </td>
                    <td class="owlstack-platform-name-col">
                        <span class="owlstack-badge owlstack-badge--<?php echo esc_attr($owlstack_platform); ?>"><?php echo esc_html($owlstack_label); ?></span>
                    </td>
                    <td class="owlstack-platform-action-col">
                        <button type="button"
                                class="button button-small owlstack-publish-single-btn"
                                data-post-id="<?php echo esc_attr((string) $post->ID); ?>"
                                data-platform="<?php echo esc_attr($owlstack_platform); ?>"
                                title="<?php echo esc_attr(sprintf(
                                    /* translators: %s: platform name */
                                    __('Publish to %s', 'owlstack-wp'),
                                    $owlstack_label
                                )); ?>">
                            <?php esc_html_e('Publish', 'owlstack-wp'); ?>
                        </button>
                        <span class="spinner"></span>
                    </td>
                    <td class="owlstack-platform-status-col">
                        <span class="owlstack-platform-result"></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <hr />

        <label class="owlstack-auto-publish">
            <input
                type="checkbox"
                name="owlstack_auto_publish"
                value="1"
                <?php checked($autoPublish); ?>
            />
            <?php esc_html_e('Auto-publish when post is published', 'owlstack-wp'); ?>
        </label>

        <hr />

        <button type="button" class="button button-primary owlstack-publish-all-btn" data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
            <?php esc_html_e('Publish All Selected', 'owlstack-wp'); ?>
        </button>
        <span class="spinner owlstack-publish-all-spinner"></span>
        <div class="owlstack-publish-status"></div>
    <?php endif; ?>
</div>
