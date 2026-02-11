<?php

declare(strict_types=1);

use Synglify\WordPress\Plugin;
use Synglify\WordPress\Publishing\SendTo;

if (! function_exists('synglify')) {
    /**
     * Get the Synglify SendTo instance for publishing content.
     *
     * Usage:
     *     synglify()->telegram('Hello!');
     *     synglify()->twitter('Hello!');
     *     synglify()->toAll($post);
     */
    function synglify(): SendTo
    {
        return Plugin::instance()->sendTo();
    }
}
