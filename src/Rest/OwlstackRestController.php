<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Rest;

use Owlstack\Core\Content\Post;
use Owlstack\WordPress\Admin\MetaBox;
use Owlstack\WordPress\Database\DeliveryLog;
use Owlstack\WordPress\Plugin;

/**
 * REST API controller for Owlstack endpoints.
 *
 * Registers routes under the `owlstack/v1` namespace:
 *   POST   /owlstack/v1/test-connection
 *   POST   /owlstack/v1/test-message
 *   POST   /owlstack/v1/publish
 *   GET    /owlstack/v1/delivery-logs
 *   DELETE /owlstack/v1/delivery-logs/(?P<id>\d+)
 */
class OwlstackRestController
{
    private const NAMESPACE = 'owlstack/v1';

    /**
     * Register REST routes.
     */
    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, '/test-connection', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [self::class, 'testConnection'],
            'permission_callback' => [self::class, 'canManage'],
            'args'                => [
                'platform' => [
                    'required'          => true,
                    'type'              => 'string',
                    'enum'              => [
                        'telegram', 'twitter', 'facebook', 'instagram',
                        'linkedin', 'discord', 'pinterest', 'reddit',
                        'slack', 'tumblr', 'whatsapp',
                    ],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/test-message', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [self::class, 'testMessage'],
            'permission_callback' => [self::class, 'canManage'],
            'args'                => [
                'platform' => [
                    'required'          => true,
                    'type'              => 'string',
                    'enum'              => [
                        'telegram', 'twitter', 'facebook', 'instagram',
                        'linkedin', 'discord', 'pinterest', 'reddit',
                        'slack', 'tumblr', 'whatsapp',
                    ],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'type' => [
                    'required'          => true,
                    'type'              => 'string',
                    'enum'              => ['text'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/publish', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [self::class, 'publish'],
            'permission_callback' => [self::class, 'canPublish'],
            'args'                => [
                'post_id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'platforms' => [
                    'required' => false,
                    'type'     => 'array',
                    'items'    => ['type' => 'string'],
                    'default'  => [],
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/delivery-logs', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [self::class, 'deliveryLogs'],
            'permission_callback' => [self::class, 'canViewLogs'],
            'args'                => [
                'page'     => ['type' => 'integer', 'default' => 1, 'minimum' => 1],
                'per_page' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100],
                'platform' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'status'   => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'post_id'  => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/delivery-logs/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [self::class, 'deleteLog'],
            'permission_callback' => [self::class, 'canManage'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
    }

    // ── Callbacks ────────────────────────────────────────────────────────

    /**
     * Test connection to a platform.
     */
    public static function testConnection(\WP_REST_Request $request): \WP_REST_Response
    {
        $platform = $request->get_param('platform');

        try {
            $plugin = Plugin::instance();
            $registry = $plugin->registry();

            if (! $registry->has($platform)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => sprintf(
                        /* translators: %s: platform name */
                        __('Platform "%s" is not configured.', 'owlstack'),
                        $platform,
                    ),
                ], 400);
            }

            $platformInstance = $registry->get($platform);

            $result = self::testPlatformConnection($platform, $platformInstance);

            $statusCode = $result['success'] ? 200 : 422;

            return new \WP_REST_Response($result, $statusCode);
        } catch (\Throwable $e) {
            wp_trigger_error(
                __METHOD__,
                '[Owlstack] Test connection error: ' . $e->getMessage(),
                E_USER_NOTICE,
            );

            return new \WP_REST_Response([
                'success' => false,
                'message' => __('An internal error occurred while testing the connection.', 'owlstack'),
            ], 500);
        }
    }

    /**
     * Send a test message to a platform.
     */
    public static function testMessage(\WP_REST_Request $request): \WP_REST_Response
    {
        $platform = $request->get_param('platform');
        $label    = self::PLATFORM_LABELS[$platform] ?? ucfirst($platform);

        try {
            $plugin   = Plugin::instance();
            $registry = $plugin->registry();

            if (! $registry->has($platform)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => sprintf(
                        /* translators: %s: platform name */
                        __('Platform "%s" is not configured. Save your credentials first.', 'owlstack'),
                        $label,
                    ),
                ], 400);
            }

            $siteTitle = get_bloginfo('name') ?: 'WordPress';
            $siteUrl   = home_url();
            $timestamp = wp_date('Y-m-d H:i:s');

            $post = new Post(
                title: sprintf('Owlstack Test — %s', $siteTitle),
                body: sprintf(
                    "This is a test message from Owlstack on %s.\n\nSite: %s\nPlatform: %s\nTime: %s\n\nIf you see this message, your %s integration is working correctly! 🎉",
                    $siteTitle,
                    $siteUrl,
                    $label,
                    $timestamp,
                    $label,
                ),
                url: $siteUrl,
                tags: ['owlstack', 'test'],
            );

            $publisher = $plugin->publisher();
            $result    = $publisher->publish($post, $platform);

            if ($result->success) {
                return new \WP_REST_Response([
                    'success'      => true,
                    'message'      => sprintf(
                        /* translators: %s: platform name */
                        __('Test message sent to %s successfully!', 'owlstack'),
                        $label,
                    ),
                    'external_id'  => $result->externalId,
                    'external_url' => $result->externalUrl,
                ]);
            }

            return new \WP_REST_Response([
                'success' => false,
                'message' => sprintf(
                    /* translators: 1: platform name, 2: error message */
                    __('Failed to send test message to %1$s: %2$s', 'owlstack'),
                    $label,
                    $result->error ?? __('Unknown error.', 'owlstack'),
                ),
            ], 422);
        } catch (\Throwable $e) {
            wp_trigger_error(
                __METHOD__,
                "[Owlstack] Test message error ({$platform}): " . $e->getMessage(),
                E_USER_NOTICE,
            );

            return new \WP_REST_Response([
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: platform name */
                    __('An error occurred while sending the test message to %s.', 'owlstack'),
                    $label,
                ),
            ], 500);
        }
    }

    /**
     * Publish a WP post to selected platforms.
     */
    public static function publish(\WP_REST_Request $request): \WP_REST_Response
    {
        $postId = (int) $request->get_param('post_id');
        $wpPost = get_post($postId);

        if (! $wpPost instanceof \WP_Post) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Post not found.', 'owlstack'),
            ], 404);
        }

        $platforms = $request->get_param('platforms');
        if (empty($platforms)) {
            $platforms = MetaBox::getSelectedPlatforms($postId);
        }

        if (empty($platforms)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('No platforms selected.', 'owlstack'),
            ], 400);
        }

        $sendTo = Plugin::instance()->sendTo();
        $post = $sendTo->buildPostFromWpPost($wpPost);

        /** @var array $options */
        $options = apply_filters('owlstack_publish_options', [], $wpPost);

        $results = [];
        foreach ($platforms as $platform) {
            $result = $sendTo->publish($post, $platform, $options, $postId);
            $results[$platform] = [
                'success'      => $result->success,
                'external_id'  => $result->externalId,
                'external_url' => $result->externalUrl,
                'error'        => $result->error,
            ];
        }

        $allSucceeded = ! in_array(false, array_column($results, 'success'), true);

        return new \WP_REST_Response([
            'success'  => $allSucceeded,
            'results'  => $results,
        ]);
    }

    /**
     * Retrieve paginated delivery logs.
     */
    public static function deliveryLogs(\WP_REST_Request $request): \WP_REST_Response
    {
        $filters = array_filter([
            'platform' => $request->get_param('platform'),
            'status'   => $request->get_param('status'),
            'post_id'  => $request->get_param('post_id'),
        ]);

        $page = (int) $request->get_param('page');
        $perPage = (int) $request->get_param('per_page');

        $result = DeliveryLog::query(array_merge($filters, [
            'page'     => $page,
            'per_page' => $perPage,
        ]));

        return new \WP_REST_Response([
            'items'    => $result['items'],
            'total'    => $result['total'],
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($result['total'] / $perPage),
        ]);
    }

    /**
     * Delete a single delivery log entry.
     */
    public static function deleteLog(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = (int) $request->get_param('id');

        $log = DeliveryLog::find($id);
        if ($log === null) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Log entry not found.', 'owlstack'),
            ], 404);
        }

        DeliveryLog::delete($id);

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Log entry deleted.', 'owlstack'),
        ]);
    }

    // ── Permission callbacks ─────────────────────────────────────────────

    public static function canManage(): bool
    {
        return current_user_can('manage_owlstack');
    }

    public static function canPublish(): bool
    {
        return current_user_can('owlstack_publish');
    }

    public static function canViewLogs(): bool
    {
        return current_user_can('owlstack_view_logs');
    }

    // ── Platform test helpers ────────────────────────────────────────────

    /**
     * Platform display names for user-facing messages.
     */
    private const PLATFORM_LABELS = [
        'telegram'  => 'Telegram',
        'twitter'   => 'Twitter / X',
        'facebook'  => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin'  => 'LinkedIn',
        'discord'   => 'Discord',
        'pinterest' => 'Pinterest',
        'reddit'    => 'Reddit',
        'slack'     => 'Slack',
        'tumblr'    => 'Tumblr',
        'whatsapp'  => 'WhatsApp',
    ];

    /**
     * Test connection to any platform using validateCredentials().
     */
    private static function testPlatformConnection(string $platform, object $platformInstance): array
    {
        $label = self::PLATFORM_LABELS[$platform] ?? ucfirst($platform);

        try {
            if ($platformInstance->validateCredentials()) {
                return [
                    'success' => true,
                    'message' => sprintf(
                        /* translators: %s: platform name */
                        __('%s connection successful.', 'owlstack'),
                        $label,
                    ),
                ];
            }

            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: platform name */
                    __('Failed to validate %s credentials.', 'owlstack'),
                    $label,
                ),
            ];
        } catch (\Throwable $e) {
            wp_trigger_error(
                __METHOD__,
                "[Owlstack] {$label} test error: " . $e->getMessage(),
                E_USER_NOTICE,
            );

            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: platform name */
                    __('Failed to connect to %s. Check your credentials and try again.', 'owlstack'),
                    $label,
                ),
            ];
        }
    }
}
