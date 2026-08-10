<?php

declare(strict_types=1);

// Prevent direct access. `return` (not `exit`) so the Composer `files`
// autoload doesn't kill CLI tools like PHPUnit that load outside WordPress.
if (! defined('ABSPATH')) {
    return;
}

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
