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
class SymfonyEventConsumer extends AbstractEventConsumer
{
    public function __construct(
        NatsConnectionFactory $connectionFactory,
        EventHandlerRegistry $registry,
        string $streamName,
        string $consumerName,
        ?string $subjectFilter = null,
        int $maxDeliver = self::DEFAULT_MAX_DELIVER,
        int $ackWait = self::DEFAULT_ACK_WAIT_MS,
    ) {
        parent::__construct(
            connection:     $connectionFactory->create(),
            handlers:       $registry->all(),
            streamName:     $streamName,
            consumerName:   $consumerName,
            subjectFilter:  $subjectFilter,
            maxDeliver:     $maxDeliver,
            ackWait:        $ackWait
        );
    }
}