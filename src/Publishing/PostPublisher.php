<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Publishing;

use Owlstack\WordPress\Admin\MetaBox;
use Owlstack\WordPress\Plugin;

/**
 * Handles automatic publishing when a WordPress post transitions to "publish" status.
 */
class PostPublisher
{
    /**
     * Called by the `transition_post_status` hook.
     */
    public static function handle(string $newStatus, string $oldStatus, \WP_Post $post): void
    {
        // Only act on new publications.
        if ($newStatus !== 'publish' || $oldStatus === 'publish') {
            return;
        }

        // Only act on supported post types.
        $supportedTypes = apply_filters('owlstack_supported_post_types', ['post']);
        if (! in_array($post->post_type, $supportedTypes, true)) {
            return;
        }

        // Check auto-publish flag.
        if (! MetaBox::isAutoPublishEnabled($post->ID)) {
            return;
        }

        $platforms = MetaBox::getSelectedPlatforms($post->ID);
        if (empty($platforms)) {
            return;
        }

        $sendTo = Plugin::instance()->sendTo();
        $corePost = $sendTo->buildPostFromWpPost($post);

        /** @var array $options */
        $options = apply_filters('owlstack_publish_options', [], $post);

        do_action('owlstack_before_publish', $corePost, $platforms, $post);

        $results = [];
        foreach ($platforms as $platform) {
            $results[$platform] = $sendTo->publish($corePost, $platform, $options, $post->ID);
        }

        do_action('owlstack_after_publish', $results, $post);

        // Fire per-result actions.
        foreach ($results as $platform => $result) {
            if ($result->isSuccess()) {
                do_action('owlstack_post_published', $result, $post, $platform);
            } else {
                do_action('owlstack_post_failed', $result, $post, $platform);
            }
        }
    }
}
