<?php

declare(strict_types=1);

namespace Owlstack\WordPress;

/**
 * Handles plugin deactivation tasks.
 */
class Deactivator
{
    /**
     * Run on plugin deactivation.
     */
    public static function deactivate(): void
    {
        self::clearScheduledEvents();
        self::removeCapabilities();

        flush_rewrite_rules();
    }

    /**
     * Clear any scheduled WP-Cron events.
     */
    private static function clearScheduledEvents(): void
    {
        wp_clear_scheduled_hook('owlstack_scheduled_publish');
    }

    /**
     * Remove custom capabilities from administrator role.
     */
    private static function removeCapabilities(): void
    {
        $role = get_role('administrator');

        if ($role === null) {
            return;
        }

        $role->remove_cap('manage_owlstack');
        $role->remove_cap('owlstack_publish');
        $role->remove_cap('owlstack_view_logs');
    }
}
