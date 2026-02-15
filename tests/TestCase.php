<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case for the Owlstack WordPress plugin.
 *
 * WP function stubs are loaded via tests/bootstrap.php in the global
 * namespace so they are available to all adapter classes.
 */
abstract class TestCase extends BaseTestCase
{
}
