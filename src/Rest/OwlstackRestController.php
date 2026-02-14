<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Rest;

use Owlstack\WordPress\Admin\MetaBox;
use Owlstack\WordPress\Database\DeliveryLog;
use Owlstack\WordPress\Plugin;

/**
 * REST API controller for Owlstack endpoints.
 *
 * Registers routes under the `owlstack/v1` namespace:
 *   POST   /owlstack/v1/test-connection
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
                    'enum'              => ['telegram', 'twitter', 'facebook'],
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
                        __('Platform "%s" is not configured.', 'owlstack-wp'),
                        $platform,
                    ),
                ], 400);
            }

            $platformInstance = $registry->get($platform);

            // Attempt a lightweight API call based on platform type.
            $result = match ($platform) {
                'telegram' => self::testTelegram($platformInstance),
                'twitter'  => self::testTwitter($platformInstance),
                'facebook' => self::testFacebook($platformInstance),
                default    => ['success' => false, 'message' => __('Test not implemented for this platform.', 'owlstack-wp')],
            };

            $statusCode = $result['success'] ? 200 : 422;

            return new \WP_REST_Response($result, $statusCode);
        } catch (\Throwable $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
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
                'message' => __('Post not found.', 'owlstack-wp'),
            ], 404);
        }

        $platforms = $request->get_param('platforms');
        if (empty($platforms)) {
            $platforms = MetaBox::getSelectedPlatforms($postId);
        }

        if (empty($platforms)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('No platforms selected.', 'owlstack-wp'),
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
                'success'      => $result->isSuccess(),
                'external_id'  => $result->externalId(),
                'external_url' => $result->externalUrl(),
                'error'        => $result->error(),
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

        $result = DeliveryLog::query($filters, $page, $perPage);

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
                'message' => __('Log entry not found.', 'owlstack-wp'),
            ], 404);
        }

        DeliveryLog::delete($id);

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Log entry deleted.', 'owlstack-wp'),
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

    private static function testTelegram(object $platform): array
    {
        try {
            // getMe is a lightweight Telegram Bot API call.
            if (method_exists($platform, 'getMe')) {
                $response = $platform->getMe();

                return [
                    'success' => true,
                    'message' => sprintf(
                        /* translators: %s: bot username */
                        __('Connected as @%s', 'owlstack-wp'),
                        $response['result']['username'] ?? 'unknown',
                    ),
                ];
            }

            return ['success' => true, 'message' => __('Platform is configured.', 'owlstack-wp')];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function testTwitter(object $platform): array
    {
        try {
            if (method_exists($platform, 'verifyCredentials')) {
                $response = $platform->verifyCredentials();

                return [
                    'success' => true,
                    'message' => sprintf(
                        /* translators: %s: twitter handle */
                        __('Connected as @%s', 'owlstack-wp'),
                        $response['screen_name'] ?? $response['username'] ?? 'unknown',
                    ),
                ];
            }

            return ['success' => true, 'message' => __('Platform is configured.', 'owlstack-wp')];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function testFacebook(object $platform): array
    {
        try {
            if (method_exists($platform, 'getPage')) {
                $response = $platform->getPage();

                return [
                    'success' => true,
                    'message' => sprintf(
                        /* translators: %s: page name */
                        __('Connected to %s', 'owlstack-wp'),
                        $response['name'] ?? 'unknown',
                    ),
                ];
            }

            return ['success' => true, 'message' => __('Platform is configured.', 'owlstack-wp')];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
