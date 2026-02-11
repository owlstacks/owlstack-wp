<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var string[] $configuredPlatforms */
/** @var string[] $selectedPlatforms */
/** @var bool $autoPublish */
/** @var WP_Post $post */

wp_nonce_field('synglify_meta_box', 'synglify_meta_box_nonce');
?>

<div class="synglify-meta-box">
    <?php if (empty($configuredPlatforms)) : ?>
        <p class="synglify-no-platforms">
            <?php
            printf(
                /* translators: %s: link open tag, %s: link close tag */
                esc_html__('No platforms configured. %1$sConfigure platforms%2$s in Synglify settings.', 'synglify-wp'),
                '<a href="' . esc_url(admin_url('admin.php?page=synglify')) . '">',
                '</a>',
            );
            ?>
        </p>
    <?php else : ?>
        <p class="synglify-meta-label"><strong><?php esc_html_e('Publish to:', 'synglify-wp'); ?></strong></p>

        <?php foreach ($configuredPlatforms as $platform) : ?>
            <label class="synglify-platform-checkbox">
                <input
                    type="checkbox"
                    name="synglify_platforms[]"
                    value="<?php echo esc_attr($platform); ?>"
                    <?php checked(in_array($platform, $selectedPlatforms, true)); ?>
                />
                <?php echo esc_html(ucfirst($platform === 'twitter' ? 'X (Twitter)' : $platform)); ?>
            </label><br />
        <?php endforeach; ?>

        <hr />

        <label class="synglify-auto-publish">
            <input
                type="checkbox"
                name="synglify_auto_publish"
                value="1"
                <?php checked($autoPublish); ?>
            />
            <?php esc_html_e('Auto-publish when post is published', 'synglify-wp'); ?>
        </label>

        <hr />

        <button type="button" class="button synglify-publish-now-btn" data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
            <?php esc_html_e('Publish Now', 'synglify-wp'); ?>
        </button>
        <span class="synglify-publish-status"></span>
    <?php endif; ?>
</div>
