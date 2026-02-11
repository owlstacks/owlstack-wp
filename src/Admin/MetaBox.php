<?php

declare(strict_types=1);

namespace Synglify\WordPress\Admin;

use Synglify\WordPress\Plugin;
use WP_Post;

/**
 * Registers the Synglify publish meta box on post edit screens.
 */
class MetaBox
{
    private const NONCE_ACTION = 'synglify_meta_box';
    private const NONCE_FIELD = 'synglify_meta_box_nonce';
    private const META_PLATFORMS = '_synglify_platforms';
    private const META_AUTO_PUBLISH = '_synglify_auto_publish';

    /**
     * Register the meta box on supported post types.
     */
    public function register(): void
    {
        /** @var string[] $postTypes */
        $postTypes = apply_filters('synglify_supported_post_types', ['post']);

        foreach ($postTypes as $postType) {
            add_meta_box(
                id: 'synglify-publish',
                title: __('Synglify — Publish to Social Media', 'synglify-wp'),
                callback: [$this, 'render'],
                screen: $postType,
                context: 'side',
                priority: 'default',
            );
        }
    }

    /**
     * Render the meta box content.
     */
    public function render(WP_Post $post): void
    {
        $configuredPlatforms = Plugin::instance()->optionsManager()->configuredPlatformNames();
        $selectedPlatforms = (array) get_post_meta($post->ID, self::META_PLATFORMS, true);
        $autoPublish = (bool) get_post_meta($post->ID, self::META_AUTO_PUBLISH, true);

        require __DIR__ . '/views/meta-box.php';
    }

    /**
     * Save meta box data when the post is saved.
     */
    public function save(int $postId, WP_Post $post): void
    {
        // Verify nonce.
        if (
            ! isset($_POST[self::NONCE_FIELD])
            || ! wp_verify_nonce($_POST[self::NONCE_FIELD], self::NONCE_ACTION)
        ) {
            return;
        }

        // Check permissions.
        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        // Skip auto-saves and revisions.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($postId)) {
            return;
        }

        // Save selected platforms.
        $platforms = isset($_POST['synglify_platforms']) && is_array($_POST['synglify_platforms'])
            ? array_map('sanitize_key', $_POST['synglify_platforms'])
            : [];

        update_post_meta($postId, self::META_PLATFORMS, $platforms);

        // Save auto-publish toggle.
        $autoPublish = isset($_POST['synglify_auto_publish']) ? '1' : '';
        update_post_meta($postId, self::META_AUTO_PUBLISH, $autoPublish);
    }

    /**
     * Get the selected platforms for a post.
     *
     * @return string[]
     */
    public static function getSelectedPlatforms(int $postId): array
    {
        $platforms = get_post_meta($postId, self::META_PLATFORMS, true);

        return is_array($platforms) ? $platforms : [];
    }

    /**
     * Check if auto-publish is enabled for a post.
     */
    public static function isAutoPublishEnabled(int $postId): bool
    {
        return (bool) get_post_meta($postId, self::META_AUTO_PUBLISH, true);
    }
}
