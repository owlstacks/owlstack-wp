<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

declare(strict_types=1);

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
