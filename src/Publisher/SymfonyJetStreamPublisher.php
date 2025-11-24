<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Publisher;

use Elandlord\NatsPhp\Contract\Message\EventMessageInterface;
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
     * @throws JsonException
     */
    public function publishEvent(EventMessageInterface $eventDto): void
    {
        $eventName = $eventDto->getEventName();

        $envelope = new EventEnvelope(
            eventName: $eventName,
            body: $eventDto
        );

        $payload = json_encode($envelope, JSON_THROW_ON_ERROR);

        $subject = $this->buildSubject($eventName);

        $this->publish($subject, $payload);
    }

    private function buildSubject(string $eventName): string
    {
        $streamRoot = strtolower($this->streamName);

        if ($this->subjectPrefix !== '') {
            return sprintf('%s.%s', $this->subjectPrefix, $eventName);
        }

        return sprintf('%s.%s', $streamRoot, $eventName);
    }
}
