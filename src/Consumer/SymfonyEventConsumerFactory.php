<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Consumer;

use Elandlord\NatsPhpBundle\Connection\NatsConnectionFactory;
use Elandlord\NatsPhpBundle\Registry\EventHandlerRegistry;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class SymfonyEventConsumerFactory
{
    public const STREAM_KEY = 'stream';
    public const NAME_KEY = 'name';
    public const SUBJECT_FILTER_KEY = 'subject_filter';
    public const MAX_DELIVER_KEY = 'max_deliver';
    public const ACK_WAIT_MS_KEY = 'ack_wait_ms';

    public function __construct(
        protected readonly NatsConnectionFactory $connectionFactory,
        protected readonly EventHandlerRegistry  $handlerRegistry
    ) {
    }

    /**
     * @param array{
     *   stream: string,
     *   name: string,
     *   subject_filter?: ?string,
     *   max_deliver?: int,
     *   ack_wait_ms?: int
     * } $definition
     */
    public function create(array $definition): SymfonyEventConsumer
    {
        return new SymfonyEventConsumer(
            connectionFactory: $this->connectionFactory,
            registry: $this->handlerRegistry,
            streamName: $definition[self::STREAM_KEY],
            consumerName: $definition[self::NAME_KEY],
            subjectFilter: $definition[self::SUBJECT_FILTER_KEY] ?? null,
            maxDeliver: $definition[self::MAX_DELIVER_KEY] ?? null,
            ackWaitMs: $definition[self::ACK_WAIT_MS_KEY] ?? null,
        );
    }
}
