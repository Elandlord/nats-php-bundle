<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Registry;

use Elandlord\NatsPhp\Contract\Handler\EventHandlerInterface;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class EventHandlerRegistry
{
    /** @var array<string, EventHandlerInterface> */
    protected array $handlers = [];

    /**
     * @param iterable<EventHandlerInterface> $handlers
     */
    public function __construct(iterable $handlers)
    {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->getHandledEventName()] = $handler;
        }
    }

    /**
     * @return array<string, EventHandlerInterface>
     */
    public function all(): array
    {
        return $this->handlers;
    }

    public function get(string $eventName): ?EventHandlerInterface
    {
        return $this->handlers[$eventName] ?? null;
    }
}
