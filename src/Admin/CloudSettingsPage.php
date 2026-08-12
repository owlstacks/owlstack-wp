<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Admin;

defined( 'ABSPATH' ) || exit;

use Owlstack\WordPress\Cloud\CloudSettings;
use Owlstack\WordPress\Cloud\CloudTokenService;

/**
 * Admin page for connecting this site to OwlStack Cloud.
 *
 * Generates/revokes the site token and manages how incoming Cloud
 * content is handled (status policy, author, post type).
 */
class CloudSettingsPage
{
    private const PAGE_SLUG = 'owlstack-cloud';

    private const REVEAL_TRANSIENT = 'owlstack_cloud_token_reveal_';

    public function __construct(
        private readonly CloudTokenService $tokens,
        private readonly CloudSettings $settings,
    ) {
    }

    /**
     * Register the submenu page.
     */
    public function register(): void
    {
        add_submenu_page(
            parent_slug: 'owlstack',
            page_title: __('Cloud', 'owlstack'),
            menu_title: __('Cloud', 'owlstack'),
            capability: 'manage_options',
            menu_slug: self::PAGE_SLUG,
            callback: [$this, 'render'],
            // Directly after Settings, ahead of the per-platform pages.
            position: 1,
        );
    }

    /**
     * Register admin-post form handlers.
     */
    public function registerActions(): void
    {
        add_action('admin_post_owlstack_cloud_generate', [$this, 'handleGenerate']);
        add_action('admin_post_owlstack_cloud_revoke', [$this, 'handleRevoke']);
        add_action('admin_post_owlstack_cloud_settings', [$this, 'handleSettings']);
    }

    /**
     * Render the Cloud settings page.
     */
    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $revealKey     = self::REVEAL_TRANSIENT . get_current_user_id();
        $revealedToken = get_transient($revealKey);
        if (is_string($revealedToken) && $revealedToken !== '') {
            delete_transient($revealKey);
        } else {
            $revealedToken = null;
        }

        $isPaired  = $this->tokens->isPaired();
        $tokenInfo = $this->tokens->info();
        $settings  = $this->settings;

        require __DIR__ . '/views/cloud-settings-page.php';
    }

    /**
     * Generate (or regenerate) the site token.
     */
    public function handleGenerate(): void
    {
        $this->verifyRequest('owlstack_cloud_generate');

        $token = $this->tokens->generate(get_current_user_id());

        // One-time reveal for the current admin only.
        set_transient(self::REVEAL_TRANSIENT . get_current_user_id(), $token, 5 * MINUTE_IN_SECONDS);

        $this->redirectBack('token-generated');
    }

    /**
     * Revoke the site token.
     */
    public function handleRevoke(): void
    {
        $this->verifyRequest('owlstack_cloud_revoke');

        $this->tokens->revoke();

        $this->redirectBack('token-revoked');
    }

    /**
     * Save content-handling settings.
     */
    public function handleSettings(): void
    {
        $this->verifyRequest('owlstack_cloud_settings');

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in verifyRequest().
        $policy = isset($_POST['post_status_policy']) ? sanitize_key(wp_unslash($_POST['post_status_policy'])) : CloudSettings::POLICY_HONOR;
        $author = isset($_POST['default_author']) ? absint(wp_unslash($_POST['default_author'])) : 0;
        $type   = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : 'post';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        $this->settings->update($policy, $author, $type);

        $this->redirectBack('settings-saved');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function verifyRequest(string $action): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to manage Owlstack Cloud settings.', 'owlstack'));
        }

        check_admin_referer($action);
    }

    private function redirectBack(string $notice): void
    {
        wp_safe_redirect(
            add_query_arg(
                ['page' => self::PAGE_SLUG, 'owlstack-notice' => $notice],
                admin_url('admin.php'),
            ),
        );
        exit;
    }
}
