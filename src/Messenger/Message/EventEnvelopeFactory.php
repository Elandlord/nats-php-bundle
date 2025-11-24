<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Message;

use Elandlord\NatsPhp\Contract\Message\EventMessageInterface;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class EventEnvelopeFactory
{
    public const EVENT_NAME_KEY = 'eventName';
    public const BODY_KEY = 'body';

    public function create(EventMessageInterface $event): array
    {
        return [
            self::EVENT_NAME_KEY => $event->getEventName(),
            self::BODY_KEY => $event->jsonSerialize(),
        ];
    }

    public function createRaw(string $eventName, array $body): array
    {
        return [
            self::EVENT_NAME_KEY => $eventName,
            self::BODY_KEY => $body,
        ];
    }
}
