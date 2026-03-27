<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Transport;

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
        protected readonly NatsReceiverRegistry  $receiverRegistry,
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

        return new NatsTransport(
            $this->connectionFactory,
            $this->receiverRegistry,
            $serializer,
            $stream,
            $consumer,
            $subjectPrefix,
            $options,
            $this->eventMap
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
