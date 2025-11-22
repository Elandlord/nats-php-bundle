<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Consumer;

use Elandlord\NatsPhp\Consumer\AbstractEventConsumer;
use Elandlord\NatsPhpBundle\Connection\NatsConnectionFactory;
use Elandlord\NatsPhpBundle\Registry\EventHandlerRegistry;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class SymfonyEventConsumerFactory
{
    public function __construct(
        private readonly NatsConnectionFactory $connectionFactory,
        private readonly EventHandlerRegistry  $handlerRegistry
    )
    {
    }

    /**
     * @param array{
     *   key: string,
     *   stream: string,
     *   subject_filter?: ?string,
     *   max_deliver?: int,
     *   ack_wait_ms?: int
     * } $definition
     */
    public function create(array $definition): SymfonyEventConsumer
    {
        $consumerName = (string)$definition['key'];
        $streamName = (string)$definition['stream'];

        $subjectFilter = $definition['subject_filter'] ?? null;
        $maxDeliver = (int)($definition['max_deliver'] ?? AbstractEventConsumer::DEFAULT_MAX_DELIVER);
        $ackWaitMs = (int)($definition['ack_wait_ms'] ?? AbstractEventConsumer::DEFAULT_ACK_WAIT_MS);

        return new SymfonyEventConsumer(
            connectionFactory: $this->connectionFactory,
            registry: $this->handlerRegistry,
            streamName: $streamName,
            consumerName: $consumerName,
            subjectFilter: $subjectFilter,
            maxDeliver: $maxDeliver,
            ackWait: $ackWaitMs
        );
    }
}
