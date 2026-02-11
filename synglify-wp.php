<?php

/**
 * Plugin Name:       Synglify
 * Plugin URI:        https://synglify.com
 * Description:       Publish content to Telegram, X (Twitter), and Facebook directly from WordPress.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Ali Hesari
 * Author URI:        https://alihesari.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       synglify-wp
 * Domain Path:       /languages
 */

declare(strict_types=1);

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('SYNGLIFY_VERSION', '0.1.0');
define('SYNGLIFY_FILE', __FILE__);
define('SYNGLIFY_DIR', plugin_dir_path(__FILE__));
define('SYNGLIFY_URL', plugin_dir_url(__FILE__));
define('SYNGLIFY_BASENAME', plugin_basename(__FILE__));

// Require Composer autoloader.
if (! file_exists(SYNGLIFY_DIR . 'vendor/autoload.php')) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Synglify requires Composer dependencies. Please run "composer install" in the plugin directory.', 'synglify-wp');
        echo '</p></div>';
    });

    return;
}

require_once SYNGLIFY_DIR . 'vendor/autoload.php';

// Boot the plugin.
$synglifyPlugin = \Synglify\WordPress\Plugin::instance();

// Activation / Deactivation hooks.
register_activation_hook(__FILE__, [\Synglify\WordPress\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [\Synglify\WordPress\Deactivator::class, 'deactivate']);

// Initialize on plugins_loaded (ensures all plugins are available).
add_action('plugins_loaded', [$synglifyPlugin, 'boot']);
