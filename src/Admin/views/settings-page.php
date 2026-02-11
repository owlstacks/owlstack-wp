<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap synglify-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('synglify_settings'); ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('synglify_settings_group');
        do_settings_sections('synglify');
        ?>

        <div class="synglify-test-connection">
            <h2><?php esc_html_e('Test Connection', 'synglify-wp'); ?></h2>
            <p><?php esc_html_e('Save your settings first, then test the connection to each platform.', 'synglify-wp'); ?></p>

            <div class="synglify-test-buttons">
                <button type="button" class="button synglify-test-btn" data-platform="telegram">
                    <?php esc_html_e('Test Telegram', 'synglify-wp'); ?>
                </button>
                <button type="button" class="button synglify-test-btn" data-platform="twitter">
                    <?php esc_html_e('Test Twitter / X', 'synglify-wp'); ?>
                </button>
                <button type="button" class="button synglify-test-btn" data-platform="facebook">
                    <?php esc_html_e('Test Facebook', 'synglify-wp'); ?>
                </button>
            </div>
            <div id="synglify-test-result" class="synglify-test-result"></div>
        </div>

        <?php submit_button(__('Save Settings', 'synglify-wp')); ?>
    </form>
</div>
