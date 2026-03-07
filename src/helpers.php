<?php

declare(strict_types=1);

// Note: No ABSPATH guard here — this file is loaded via Composer autoload_files
// and must not call exit() outside of a WordPress context.

use Owlstack\WordPress\Plugin;
use Owlstack\WordPress\Publishing\SendTo;

if (! function_exists('owlstack')) {
    /**
     * Get the Owlstack SendTo instance for publishing content.
     *
     * Usage:
     *     owlstack()->telegram('Hello!');
     *     owlstack()->twitter('Hello!');
     *     owlstack()->toAll($post);
     */
    function owlstack(): SendTo
    {
        return Plugin::instance()->sendTo();
    }
}
