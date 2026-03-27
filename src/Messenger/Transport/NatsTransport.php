<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Transport;

use CloudEvents\Exceptions\UnsupportedSpecVersionException;
use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhpBundle\Connection\NatsConnectionFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Throwable;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsTransport implements TransportInterface
{
    protected ?NatsConnection $connection = null;
    protected ?NatsTransportSender $sender = null;
    protected ?NatsTransportReceiver $receiver = null;

    /**
     * @param array<string, class-string> $eventMap
     */
    public function __construct(
        protected readonly NatsConnectionFactory $connectionFactory,
        protected readonly NatsReceiverRegistry  $receiverRegistry,
        protected SerializerInterface            $serializer,
        protected string                         $stream,
        protected string                         $consumer,
        protected ?string                        $subjectPrefix,
        protected array                          $options,
        protected readonly array                 $eventMap
    )
    {

    }

    /**
     * @throws UnsupportedSpecVersionException
     */
    public function send(Envelope $envelope): Envelope
    {
        return $this->getOrCreateSender()->send($envelope);
    }

    /**
     * @throws Throwable
     */
    public function get(): iterable
    {
        return $this->getOrCreateReceiver()->get();
    }

    public function ack(Envelope $envelope): void
    {
        $this->getOrCreateReceiver()->ack($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        $this->getOrCreateReceiver()->reject($envelope);
    }

    protected function getOrCreateConnection(): NatsConnection
    {
        if ($this->connection === null) {
            $this->connection = $this->connectionFactory->create();
        }
        return $this->connection;
    }

    protected function getOrCreateSender(): NatsTransportSender
    {
        if ($this->sender === null) {
            $this->sender = new NatsTransportSender(
                connection: $this->getOrCreateConnection(),
                serializer: $this->serializer,
                stream: $this->stream,
                subjectPrefix: $this->subjectPrefix
            );
        }
        return $this->sender;
    }

    protected function getOrCreateReceiver(): NatsTransportReceiver
    {
        $receiver = new NatsTransportReceiver(
            connection: $this->getOrCreateConnection(),
            serializer: $this->serializer,
            stream: $this->stream,
            consumer: $this->consumer,
            subjectFilter: $this->options['subject_filter'] ?? null,
            maxDeliver: (int)($this->options['max_deliver'] ?? NatsTransportReceiver::DEFAULT_MAX_DELIVER),
            ackWaitMs: (int)($this->options['ack_wait_ms'] ?? NatsTransportReceiver::DEFAULT_ACK_WAIT_MS),
            timeoutMs: (int)($this->options['timeout_ms'] ?? NatsTransportReceiver::DEFAULT_TIMEOUT_MS),
            eventMap: $this->eventMap
        );
        $this->receiverRegistry->register($receiver);
        return $receiver;
    }
}
