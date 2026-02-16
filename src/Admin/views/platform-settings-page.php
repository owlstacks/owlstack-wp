<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var array{label: string, description: string, fields: array} $platform */
/** @var string $platformSlug */
/** @var \Owlstack\WordPress\Admin\OptionsManager $optionsManager */
?>
<div class="wrap owlstack-settings owlstack-platform-settings">
    <h1>
        <?php
        printf(
            /* translators: %s: platform name */
            esc_html__('Owlstack — %s Settings', 'owlstack-wp'),
            esc_html($platform['label']),
        );
        ?>
    </h1>

    <?php settings_errors('owlstack_settings'); ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('owlstack_settings_group');
        do_settings_sections("owlstack-{$platformSlug}");
        ?>

        <div class="owlstack-test-connection">
            <h2><?php esc_html_e('Test Connection', 'owlstack-wp'); ?></h2>
            <p><?php esc_html_e('Save your settings first, then test the connection.', 'owlstack-wp'); ?></p>

            <div class="owlstack-test-buttons">
                <button type="button" class="button owlstack-test-btn" data-platform="<?php echo esc_attr($platformSlug); ?>">
                    <?php
                    printf(
                        /* translators: %s: platform name */
                        esc_html__('Test %s', 'owlstack-wp'),
                        esc_html($platform['label']),
                    );
                    ?>
                </button>
                <span class="spinner"></span>
            </div>
            <div id="owlstack-test-result" class="owlstack-test-result"></div>
        </div>

        <?php submit_button(__('Save Settings', 'owlstack-wp')); ?>
    </form>

    <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=owlstack')); ?>">&larr; <?php esc_html_e('Back to Settings Overview', 'owlstack-wp'); ?></a>
    </p>
</div>
