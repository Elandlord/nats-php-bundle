<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Publisher;

use CloudEvents\Exceptions\UnsupportedSpecVersionException;
use CloudEvents\Serializers\JsonSerializer;
use CloudEvents\V1\CloudEventInterface;
use Elandlord\NatsPhp\Contract\Model\SubjectPublisherInterface;
use Elandlord\NatsPhp\Publisher\JetStreamPublisher;
use Elandlord\NatsPhpBundle\Connection\NatsConnectionFactory;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class SymfonyJetStreamPublisher implements SubjectPublisherInterface
{
    public function __construct(
        protected NatsConnectionFactory $connectionFactory,
        protected string $streamName,
        protected string $subjectPrefix = ''
    ) {
    }

    public function publish(string $subject, string $payload): void
    {
        $this->createPublisher()->publish($subject, $payload);
    }

    public function publishFireAndForget(string $subject, string $payload): void
    {
        $this->createPublisher()->publishFireAndForget($subject, $payload);
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

    protected function createPublisher(): JetStreamPublisher
    {
        $connection = $this->connectionFactory->create();

        return new JetStreamPublisher($connection, $this->streamName);
    }
}
