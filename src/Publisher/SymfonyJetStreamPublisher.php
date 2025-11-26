<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Publisher;

use CloudEvents\Exceptions\UnsupportedSpecVersionException;
use CloudEvents\Serializers\JsonSerializer;
use CloudEvents\V1\CloudEventInterface;
use Elandlord\NatsPhp\Contract\Model\SubjectPublisherInterface;
use Elandlord\NatsPhp\Messaging\EventEnvelope;
use Elandlord\NatsPhp\Publisher\JetStreamPublisher;
use Elandlord\NatsPhpBundle\Connection\NatsConnectionFactory;
use JsonException;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class SymfonyJetStreamPublisher implements SubjectPublisherInterface
{
    protected JetStreamPublisher $publisher;

    public function __construct(
        NatsConnectionFactory $connectionFactory,
        protected string $streamName,
        protected string $subjectPrefix = ''
    ) {
        $connection = $connectionFactory->create();
        $this->publisher = new JetStreamPublisher($connection, $this->streamName);
    }

    public function publish(string $subject, string $payload): void
    {
        $this->publisher->publish($subject, $payload);
    }

    /**
     * @throws UnsupportedSpecVersionException
     */
    public function publishEvent(CloudEventInterface $eventDto): void
    {
        $payload = JsonSerializer::create()->serializeStructured($eventDto);
        $subject = $eventDto->getType();

        $this->publish($subject, $payload);
    }
}
