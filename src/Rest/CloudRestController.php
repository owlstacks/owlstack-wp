<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Rest;

defined( 'ABSPATH' ) || exit;

use Owlstack\WordPress\Cloud\CloudSettings;
use Owlstack\WordPress\Cloud\CloudTokenService;

/**
 * REST API endpoints for OwlStack Cloud content push.
 *
 * Registers routes under `owlstack/v1/cloud`:
 *   GET    /owlstack/v1/cloud/site
 *   POST   /owlstack/v1/cloud/posts
 *   POST   /owlstack/v1/cloud/media
 *   DELETE /owlstack/v1/cloud/posts/(?P<id>\d+)
 *
 * All routes authenticate via the X-Owlstack-Token header against the
 * hashed site token — no WordPress user session or nonce is involved.
 * The token can only reach these routes; it grants no other WP access.
 */
class CloudRestController
{
    private const NAMESPACE = 'owlstack/v1';

    private const META_KEY = '_owlstack_cloud';

    public const ALLOWED_MIME_TYPES = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        'gif'          => 'image/gif',
        'webp'         => 'image/webp',
    ];

    /**
     * Register REST routes.
     */
    public static function register(): void
    {
        register_rest_route(self::NAMESPACE, '/cloud/site', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [self::class, 'siteInfo'],
            'permission_callback' => [self::class, 'checkToken'],
        ]);

        register_rest_route(self::NAMESPACE, '/cloud/posts', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [self::class, 'createPost'],
            'permission_callback' => [self::class, 'checkToken'],
            'args'                => [
                'title' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'content' => [
                    'required' => true,
                    'type'     => 'string',
                    // Sanitized with wp_kses_post in the callback.
                ],
                'status' => [
                    'required'          => false,
                    'type'              => 'string',
                    'enum'              => ['draft', 'publish'],
                    'default'           => 'publish',
                    'sanitize_callback' => 'sanitize_key',
                ],
                'featured_media' => [
                    'required'          => false,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'excerpt' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'slug' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_title',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/cloud/media', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [self::class, 'uploadMedia'],
            'permission_callback' => [self::class, 'checkToken'],
        ]);

        register_rest_route(self::NAMESPACE, '/cloud/posts/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [self::class, 'deletePost'],
            'permission_callback' => [self::class, 'checkToken'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'force' => [
                    'required' => false,
                    'type'     => 'boolean',
                    'default'  => false,
                ],
            ],
        ]);
    }

    // ── Auth ─────────────────────────────────────────────────────────────

    /**
     * Validate the X-Owlstack-Token header against the stored hash.
     */
    public static function checkToken(\WP_REST_Request $request): bool|\WP_Error
    {
        $tokens = self::tokens();

        if (! $tokens->isPaired()) {
            return new \WP_Error(
                'owlstack_not_paired',
                __('No site token has been generated. Create one under Owlstack → Cloud.', 'owlstack'),
                ['status' => 403],
            );
        }

        $provided = (string) $request->get_header('X-Owlstack-Token');

        if ($provided === '' || ! $tokens->verify($provided)) {
            return new \WP_Error(
                'owlstack_invalid_token',
                __('Invalid site token.', 'owlstack'),
                ['status' => 401],
            );
        }

        $tokens->touch();

        return true;
    }

    // ── Callbacks ────────────────────────────────────────────────────────

    /**
     * Site metadata used by OwlStack Cloud to validate and label the connection.
     */
    public static function siteInfo(): \WP_REST_Response
    {
        $settings = self::settings();

        return new \WP_REST_Response([
            'name'               => get_bloginfo('name'),
            'url'                => home_url(),
            'icon'               => get_site_icon_url() ?: null,
            'wp_version'         => get_bloginfo('version'),
            'plugin_version'     => defined('OWLSTACK_VERSION') ? OWLSTACK_VERSION : null,
            'post_status_policy' => $settings->statusPolicy(),
            'default_post_type'  => $settings->postType(),
        ]);
    }

    /**
     * Create a post from OwlStack Cloud content.
     */
    public static function createPost(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $settings = self::settings();

        $author = $settings->defaultAuthor();
        if ($author === 0) {
            return new \WP_Error(
                'owlstack_no_author',
                __('No valid author is configured for Cloud posts. Check Owlstack → Cloud settings.', 'owlstack'),
                ['status' => 500],
            );
        }

        $status = $settings->resolveStatus((string) $request->get_param('status'));

        $postarr = [
            'post_title'   => (string) $request->get_param('title'),
            'post_content' => wp_kses_post((string) $request->get_param('content')),
            'post_status'  => $status,
            'post_type'    => $settings->postType(),
            'post_author'  => $author,
            'meta_input'   => [self::META_KEY => 1],
        ];

        $excerpt = $request->get_param('excerpt');
        if (is_string($excerpt) && $excerpt !== '') {
            $postarr['post_excerpt'] = $excerpt;
        }

        $slug = $request->get_param('slug');
        if (is_string($slug) && $slug !== '') {
            $postarr['post_name'] = $slug;
        }

        $postId = wp_insert_post(wp_slash($postarr), true);

        if (is_wp_error($postId)) {
            $postId->add_data(['status' => 500]);

            return $postId;
        }

        $featuredMedia = (int) ($request->get_param('featured_media') ?? 0);
        if ($featuredMedia > 0 && get_post_type($featuredMedia) === 'attachment') {
            set_post_thumbnail($postId, $featuredMedia);
        }

        return new \WP_REST_Response([
            'id'        => $postId,
            'status'    => get_post_status($postId),
            'link'      => get_permalink($postId),
            'edit_link' => admin_url(sprintf('post.php?post=%d&action=edit', $postId)),
        ], 201);
    }

    /**
     * Sideload an image (raw request body, wp/v2-style headers).
     */
    public static function uploadMedia(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $bits = $request->get_body();
        if ($bits === '') {
            return new \WP_Error(
                'owlstack_empty_upload',
                __('No file content received.', 'owlstack'),
                ['status' => 400],
            );
        }

        $filename = self::filenameFromDisposition((string) $request->get_header('Content-Disposition'));
        if ($filename === '') {
            return new \WP_Error(
                'owlstack_missing_filename',
                __('Content-Disposition header with a filename is required.', 'owlstack'),
                ['status' => 400],
            );
        }

        $type = wp_check_filetype($filename, self::ALLOWED_MIME_TYPES);
        if (empty($type['ext']) || empty($type['type'])) {
            return new \WP_Error(
                'owlstack_unsupported_type',
                __('Only JPEG, PNG, GIF, and WebP images are supported.', 'owlstack'),
                ['status' => 415],
            );
        }

        $upload = wp_upload_bits($filename, null, $bits);
        if (! empty($upload['error'])) {
            return new \WP_Error(
                'owlstack_upload_failed',
                (string) $upload['error'],
                ['status' => 500],
            );
        }

        // Verify the actual bytes match an allowed image type, not just the name.
        $real = wp_check_filetype_and_ext($upload['file'], $filename, self::ALLOWED_MIME_TYPES);
        if (empty($real['ext']) || empty($real['type'])) {
            wp_delete_file($upload['file']);

            return new \WP_Error(
                'owlstack_invalid_image',
                __('The uploaded file is not a valid image.', 'owlstack'),
                ['status' => 415],
            );
        }

        $attachmentId = wp_insert_attachment(
            [
                'post_mime_type' => $real['type'],
                'post_title'     => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                'post_status'    => 'inherit',
                'post_author'    => self::settings()->defaultAuthor(),
                'meta_input'     => [self::META_KEY => 1],
            ],
            $upload['file'],
            0,
            true,
        );

        if (is_wp_error($attachmentId)) {
            wp_delete_file($upload['file']);
            $attachmentId->add_data(['status' => 500]);

            return $attachmentId;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        wp_update_attachment_metadata(
            $attachmentId,
            wp_generate_attachment_metadata($attachmentId, $upload['file']),
        );

        return new \WP_REST_Response([
            'id'         => $attachmentId,
            'source_url' => wp_get_attachment_url($attachmentId) ?: $upload['url'],
        ], 201);
    }

    /**
     * Trash or delete a post previously created by OwlStack Cloud.
     */
    public static function deletePost(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $postId = (int) $request->get_param('id');
        $post   = get_post($postId);

        if (! $post instanceof \WP_Post) {
            return new \WP_Error(
                'owlstack_post_not_found',
                __('Post not found.', 'owlstack'),
                ['status' => 404],
            );
        }

        // The token may only touch content it created.
        if (! get_post_meta($postId, self::META_KEY, true)) {
            return new \WP_Error(
                'owlstack_forbidden_post',
                __('This post was not created by OwlStack Cloud.', 'owlstack'),
                ['status' => 403],
            );
        }

        $force  = (bool) $request->get_param('force');
        $result = $force ? wp_delete_post($postId, true) : wp_trash_post($postId);

        if (! $result) {
            return new \WP_Error(
                'owlstack_delete_failed',
                __('Failed to delete the post.', 'owlstack'),
                ['status' => 500],
            );
        }

        return new \WP_REST_Response([
            'deleted' => true,
            'id'      => $postId,
        ]);
    }

    // ── Services ─────────────────────────────────────────────────────────

    private static function tokens(): CloudTokenService
    {
        return new CloudTokenService();
    }

    private static function settings(): CloudSettings
    {
        return new CloudSettings(self::tokens());
    }

    /**
     * Extract the filename from a Content-Disposition header.
     */
    private static function filenameFromDisposition(string $disposition): string
    {
        if (! preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $disposition, $matches)) {
            return '';
        }

        return sanitize_file_name(rawurldecode(trim($matches[1])));
    }
}
