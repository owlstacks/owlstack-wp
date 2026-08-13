<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var array $platforms */
/** @var \Owlstack\WordPress\Admin\OptionsManager $optionsManager */
/** @var string[] $configuredNames */
?>
<div class="wrap owlstack-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php
    if (! empty($_GET['settings-updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        add_settings_error('owlstack_settings', 'owlstack_updated', __('Settings saved.', 'owlstack'), 'updated');
    }
    settings_errors('owlstack_settings');
    ?>

    <?php if (! \Owlstack\WordPress\Admin\CloudPromo::isDismissed()): ?>
        <div class="owlstack-cloud-promo notice notice-info">
            <h2 class="owlstack-cloud-promo__title">
                <?php esc_html_e('Publish beyond this site with OwlStack Cloud', 'owlstack'); ?>
            </h2>
            <p>
                <?php
                printf(
                    /* translators: 1: platforms this plugin supports, 2: platforms OwlStack Cloud supports */
                    esc_html__('This plugin publishes to %1$d platforms straight from WordPress using your own credentials, and it always will. OwlStack Cloud reaches %2$d platforms and adds scheduling, a content calendar, analytics, and AI drafting, with this site as one of its destinations.', 'owlstack'),
                    count($platforms),
                    (int) \Owlstack\WordPress\Admin\CloudPromo::CLOUD_PLATFORM_COUNT,
                );
                ?>
            </p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=owlstack-cloud')); ?>">
                    <?php esc_html_e('Connect this site to Cloud', 'owlstack'); ?>
                </a>
                <a class="button" href="<?php echo esc_url(\Owlstack\WordPress\Admin\CloudPromo::url('/', 'settings-card')); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Start a free trial', 'owlstack'); ?>
                </a>
                <a class="owlstack-cloud-promo__dismiss" href="<?php echo esc_url(\Owlstack\WordPress\Admin\CloudPromo::dismissUrl()); ?>">
                    <?php esc_html_e('Dismiss', 'owlstack'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Platform overview -->
    <h2><?php esc_html_e('Platforms', 'owlstack'); ?></h2>
    <p><?php esc_html_e('Configure each platform from its own settings page. Platforms with valid credentials are marked as connected.', 'owlstack'); ?></p>

    <table class="wp-list-table widefat fixed striped owlstack-platform-overview">
        <thead>
            <tr>
                <th><?php esc_html_e('Platform', 'owlstack'); ?></th>
                <th><?php esc_html_e('Status', 'owlstack'); ?></th>
                <th><?php esc_html_e('Actions', 'owlstack'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($platforms as $owlstack_key => $owlstack_platform): ?>
                <?php $owlstack_is_configured = in_array($owlstack_key, $configuredNames, true); ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($owlstack_platform['label']); ?></strong>
                    </td>
                    <td>
                        <?php if ($owlstack_is_configured): ?>
                            <span class="owlstack-badge owlstack-badge--success"><?php esc_html_e('Connected', 'owlstack'); ?></span>
                        <?php else: ?>
                            <span class="owlstack-badge owlstack-badge--pending"><?php esc_html_e('Not configured', 'owlstack'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url(admin_url("admin.php?page=owlstack-{$owlstack_key}")); ?>" class="button button-small">
                            <?php esc_html_e('Configure', 'owlstack'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Proxy settings -->
    <form method="post" action="options.php">
        <?php
        settings_fields('owlstack_settings_group');
        do_settings_sections('owlstack');
        submit_button(__('Save Settings', 'owlstack'));
        ?>
    </form>

    <!-- Support section -->
    <div class="owlstack-support-section">
        <h2><?php esc_html_e('Need Help?', 'owlstack'); ?></h2>
        <p>
            <?php
            printf(
                /* translators: %s: link to OwlStack documentation */
                esc_html__('Need help setting up your social media platforms such as Telegram, Twitter, Facebook, Instagram, LinkedIn, or others? Our documentation covers step-by-step guides for configuring each platform with the WordPress plugin. Visit the %s to get started.', 'owlstack'),
                '<a href="https://owlstack.app/docs/sdks/wordpress/installation" target="_blank" rel="noopener noreferrer">' . esc_html__('OwlStack Documentation', 'owlstack') . '</a>'
            );
            ?>
        </p>
    </div>
</div>
