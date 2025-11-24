<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Transport;

use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhpBundle\Connection\NatsConnectionFactory;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Exception\TransportException;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsTransportFactory implements TransportFactoryInterface
{
    /**
     * @param array<string, class-string> $eventMap
     */
    public function __construct(
        protected readonly NatsConnectionFactory $connectionFactory,
        protected readonly array                 $eventMap = []
    )
    {
    }

    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'nats://');
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        if (!$this->supports($dsn, $options)) {
            throw new TransportException(sprintf('The DSN "%s" is not supported by NatsTransportFactory.', $dsn));
        }

        $parsed = $this->parseDsn($dsn);
        $options = array_merge($parsed['options'], $options);

        $stream = $options['stream'] ?? null;
        $consumer = $options['consumer'] ?? null;
        $subjectPrefix = $options['subject_prefix'] ?? null;

        if (!$stream || !$consumer) {
            throw new TransportException('Options "stream" and "consumer" are required for NATS Messenger transport.');
        }

        $connection = $this->connectionFactory->create();

        $sender = $this->createSender($connection, $serializer, $stream, $subjectPrefix);
        $receiver = $this->createReceiver($connection, $serializer, $stream, $consumer);

        return new NatsTransport($sender, $receiver);
    }

    protected function createSender(
        NatsConnection      $connection,
        SerializerInterface $serializer,
        string              $stream,
        ?string             $subjectPrefix = null
    ): NatsTransportSender
    {
        return new NatsTransportSender(
            connection: $connection,
            serializer: $serializer,
            stream: $stream,
            subjectPrefix: $subjectPrefix
        );
    }

    protected function createReceiver(
        NatsConnection      $connection,
        SerializerInterface $serializer,
        string              $stream,
        string              $consumer

    ): NatsTransportReceiver
    {
        return new NatsTransportReceiver(
            connection: $connection,
            serializer: $serializer,
            stream: $stream,
            consumer: $consumer,
            subjectFilter: $options['subject_filter'] ?? null,
            maxDeliver: (int)($options['max_deliver'] ?? NatsTransportReceiver::DEFAULT_MAX_DELIVER),
            ackWaitMs: (int)($options['ack_wait_ms'] ?? NatsTransportReceiver::DEFAULT_ACK_WAIT_MS),
            timeoutMs: (int)($options['timeout_ms'] ?? NatsTransportReceiver::DEFAULT_TIMEOUT_MS),
            eventMap: $this->eventMap
        );
    }

    protected function parseDsn(string $dsn): array
    {
        $parts = parse_url($dsn) ?: [];

        $query = $parts['query'] ?? '';
        parse_str($query, $queryOptions);

        return [
            'options' => $queryOptions,
        ];
    }
}
