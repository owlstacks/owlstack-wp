<?php

/**
 * Owlstack Uninstall
 *
 * Fired when the plugin is uninstalled. Cleans up all plugin data
 * including options, custom database tables, and capabilities.
 *
 * @package Owlstack\WordPress
 */

declare(strict_types=1);

// If uninstall not called from WordPress, exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load Composer autoloader.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

\Owlstack\WordPress\Uninstaller::uninstall();
