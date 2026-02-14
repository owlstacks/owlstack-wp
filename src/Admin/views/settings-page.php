<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap owlstack-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('owlstack_settings'); ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('owlstack_settings_group');
        do_settings_sections('owlstack');
        ?>

        <div class="owlstack-test-connection">
            <h2><?php esc_html_e('Test Connection', 'owlstack-wp'); ?></h2>
            <p><?php esc_html_e('Save your settings first, then test the connection to each platform.', 'owlstack-wp'); ?></p>

            <div class="owlstack-test-buttons">
                <button type="button" class="button owlstack-test-btn" data-platform="telegram">
                    <?php esc_html_e('Test Telegram', 'owlstack-wp'); ?>
                </button>
                <button type="button" class="button owlstack-test-btn" data-platform="twitter">
                    <?php esc_html_e('Test Twitter / X', 'owlstack-wp'); ?>
                </button>
                <button type="button" class="button owlstack-test-btn" data-platform="facebook">
                    <?php esc_html_e('Test Facebook', 'owlstack-wp'); ?>
                </button>
            </div>
            <div id="owlstack-test-result" class="owlstack-test-result"></div>
        </div>

        <?php submit_button(__('Save Settings', 'owlstack-wp')); ?>
    </form>
</div>
