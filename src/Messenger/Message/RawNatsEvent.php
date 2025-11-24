<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Message;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class RawNatsEvent
{
    public function __construct(
        public readonly string $eventName,
        public readonly array $body
    ) {}
}
