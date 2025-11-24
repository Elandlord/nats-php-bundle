<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Throwable;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsTransport implements TransportInterface
{
    public function __construct(
        protected readonly NatsTransportSender $sender,
        protected readonly NatsTransportReceiver $receiver
    ) {
    }

    public function send(Envelope $envelope): Envelope
    {
        return $this->sender->send($envelope);
    }

    /**
     * @throws Throwable
     */
    public function get(): iterable
    {
        return $this->receiver->get();
    }

    public function ack(Envelope $envelope): void
    {
        $this->receiver->ack($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        $this->receiver->reject($envelope);
    }
}
