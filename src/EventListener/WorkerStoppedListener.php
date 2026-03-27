<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\EventListener;

use Elandlord\NatsPhpBundle\Messenger\Transport\NatsReceiverRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;

/**
 * Unsubscribes all NATS receivers when the Messenger worker stops,
 * allowing the NATS server to clean up unlistened queues.
 *
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class WorkerStoppedListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly NatsReceiverRegistry $receiverRegistry,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerStoppedEvent::class => 'onWorkerStopped',
        ];
    }

    public function onWorkerStopped(WorkerStoppedEvent $event): void
    {
        foreach ($this->receiverRegistry->all() as $receiver) {
            $receiver->unsubscribe();
        }
    }
}
