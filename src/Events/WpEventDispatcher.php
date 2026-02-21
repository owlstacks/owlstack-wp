<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

declare(strict_types=1);

namespace Owlstack\WordPress\Events;

use Owlstack\Core\Events\Contracts\EventDispatcherInterface;

/**
 * Bridges Owlstack Core's EventDispatcherInterface to WordPress actions.
 *
 * Dispatches events as WordPress actions so developers can hook in
 * using standard add_action() calls:
 *
 *     add_action('owlstack_post_published', function (PostPublished $event) { ... });
 *     add_action('owlstack_post_failed', function (PostFailed $event) { ... });
 */
class WpEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): void
    {
        $className = (new \ReflectionClass($event))->getShortName();
        $hookName = 'owlstack_' . $this->toSnakeCase($className);

        /** @phpstan-ignore-next-line */
        do_action($hookName, $event);
    }

    /**
     * Convert PascalCase to snake_case.
     */
    private function toSnakeCase(string $input): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
