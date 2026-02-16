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

    <?php settings_errors('owlstack_settings'); ?>

    <!-- Platform overview -->
    <h2><?php esc_html_e('Platforms', 'owlstack-wp'); ?></h2>
    <p><?php esc_html_e('Configure each platform from its own settings page. Platforms with valid credentials are marked as connected.', 'owlstack-wp'); ?></p>

    <table class="wp-list-table widefat fixed striped owlstack-platform-overview">
        <thead>
            <tr>
                <th><?php esc_html_e('Platform', 'owlstack-wp'); ?></th>
                <th><?php esc_html_e('Status', 'owlstack-wp'); ?></th>
                <th><?php esc_html_e('Actions', 'owlstack-wp'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($platforms as $key => $platform): ?>
                <?php $isConfigured = in_array($key, $configuredNames, true); ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($platform['label']); ?></strong>
                    </td>
                    <td>
                        <?php if ($isConfigured): ?>
                            <span class="owlstack-badge owlstack-badge--success"><?php esc_html_e('Connected', 'owlstack-wp'); ?></span>
                        <?php else: ?>
                            <span class="owlstack-badge owlstack-badge--pending"><?php esc_html_e('Not configured', 'owlstack-wp'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url(admin_url("admin.php?page=owlstack-{$key}")); ?>" class="button button-small">
                            <?php esc_html_e('Configure', 'owlstack-wp'); ?>
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
        submit_button(__('Save Settings', 'owlstack-wp'));
        ?>
    </form>
</div>
