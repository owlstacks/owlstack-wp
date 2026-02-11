<?php

declare(strict_types=1);

namespace Synglify\WordPress;

use Synglify\WordPress\Database\DeliveryLogTable;

/**
 * Handles plugin activation tasks.
 */
class Activator
{
    /**
     * Run on plugin activation.
     */
    public static function activate(): void
    {
        self::createTables();
        self::setDefaultOptions();
        self::addCapabilities();

        flush_rewrite_rules();
    }

    /**
     * Create custom database tables.
     */
    private static function createTables(): void
    {
        DeliveryLogTable::create();
    }

    /**
     * Set default plugin options if not already set.
     */
    private static function setDefaultOptions(): void
    {
        if (get_option('synglify_settings') === false) {
            $defaults = [
                'platforms' => [
                    'telegram' => [
                        'api_token'         => '',
                        'bot_username'      => '',
                        'channel_username'  => '',
                        'channel_signature' => '',
                        'parse_mode'        => 'HTML',
                    ],
                    'twitter' => [
                        'consumer_key'        => '',
                        'consumer_secret'     => '',
                        'access_token'        => '',
                        'access_token_secret' => '',
                    ],
                    'facebook' => [
                        'app_id'                => '',
                        'app_secret'            => '',
                        'page_access_token'     => '',
                        'page_id'               => '',
                        'default_graph_version' => 'v21.0',
                    ],
                ],
                'proxy' => [
                    'type'     => '',
                    'hostname' => '',
                    'port'     => '',
                    'username' => '',
                    'password' => '',
                ],
            ];

            add_option('synglify_settings', $defaults);
        }

        if (get_option('synglify_db_version') === false) {
            add_option('synglify_db_version', '1.0.0');
        }
    }

    /**
     * Add custom capabilities to administrator role.
     */
    private static function addCapabilities(): void
    {
        $role = get_role('administrator');

        if ($role === null) {
            return;
        }

        $role->add_cap('manage_synglify');
        $role->add_cap('synglify_publish');
        $role->add_cap('synglify_view_logs');
    }
}
