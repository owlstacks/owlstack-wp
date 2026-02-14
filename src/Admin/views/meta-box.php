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

        <?php foreach ($configuredPlatforms as $platform) : ?>
            <label class="owlstack-platform-checkbox">
                <input
                    type="checkbox"
                    name="owlstack_platforms[]"
                    value="<?php echo esc_attr($platform); ?>"
                    <?php checked(in_array($platform, $selectedPlatforms, true)); ?>
                />
                <?php echo esc_html(ucfirst($platform === 'twitter' ? 'X (Twitter)' : $platform)); ?>
            </label><br />
        <?php endforeach; ?>

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

        <button type="button" class="button owlstack-publish-now-btn" data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
            <?php esc_html_e('Publish Now', 'owlstack-wp'); ?>
        </button>
        <span class="owlstack-publish-status"></span>
    <?php endif; ?>
</div>
