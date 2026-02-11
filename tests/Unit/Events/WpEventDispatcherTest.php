<?php

declare(strict_types=1);

namespace Synglify\WordPress\Tests\Unit\Events;

use Synglify\WordPress\Events\WpEventDispatcher;
use Synglify\WordPress\Tests\TestCase;

class WpEventDispatcherTest extends TestCase
{
    private WpEventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new WpEventDispatcher();
    }

    public function test_it_implements_event_dispatcher_interface(): void
    {
        $this->assertInstanceOf(
            \Synglify\Core\Events\EventDispatcherInterface::class,
            $this->dispatcher,
        );
    }

    public function test_dispatch_does_not_throw(): void
    {
        // With stubs, dispatch should execute without errors.
        $this->dispatcher->dispatch('test_event', ['key' => 'value']);

        $this->assertTrue(true); // No exception thrown.
    }

    public function test_dispatch_with_object_event(): void
    {
        $event = new class {
            public string $name = 'TestEvent';
        };

        // Should convert class name to snake_case hook and dispatch.
        $this->dispatcher->dispatch($event);

        $this->assertTrue(true); // No exception thrown.
    }
}
