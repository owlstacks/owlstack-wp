<?php

/**
 * Plugin Name:       Owlstack
 * Plugin URI:        https://owlstack.dev
 * Description:       Publish content to Telegram, X (Twitter), Facebook, Instagram, LinkedIn, Discord, and more — directly from WordPress.
 * Version:           1.0.1
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Ali Hesari
 * Author URI:        https://alihesari.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       owlstack
 * Domain Path:       /languages
 */

declare(strict_types=1);

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('OWLSTACK_VERSION', '1.0.1');
define('OWLSTACK_FILE', __FILE__);
define('OWLSTACK_DIR', plugin_dir_path(__FILE__));
define('OWLSTACK_URL', plugin_dir_url(__FILE__));
define('OWLSTACK_BASENAME', plugin_basename(__FILE__));

// Require Composer autoloader.
if (! file_exists(OWLSTACK_DIR . 'vendor/autoload.php')) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Owlstack requires Composer dependencies. Please run "composer install" in the plugin directory.', 'owlstack');
        echo '</p></div>';
    });

    return;
}

require_once OWLSTACK_DIR . 'vendor/autoload.php';

// Boot the plugin.
$owlstackPlugin = \Owlstack\WordPress\Plugin::instance();

// Activation / Deactivation hooks.
register_activation_hook(__FILE__, [\Owlstack\WordPress\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [\Owlstack\WordPress\Deactivator::class, 'deactivate']);

// Initialize on plugins_loaded (ensures all plugins are available).
add_action('plugins_loaded', [$owlstackPlugin, 'boot']);
