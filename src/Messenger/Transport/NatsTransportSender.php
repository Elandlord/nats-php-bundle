<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Transport;

use Elandlord\NatsPhp\Connection\NatsConnection;
use JsonException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Exception\TransportException;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsTransportSender implements SenderInterface
{
    public function __construct(
        protected readonly NatsConnection $connection,
        protected readonly SerializerInterface $serializer,
        protected readonly string $stream,
        protected readonly ?string $subjectPrefix = null
    ) {
    }

    public function send(Envelope $envelope): Envelope
    {
        $client = $this->connection->getClient();

        $subject = $this->buildSubjectFromEnvelope($envelope);

        $encoded = $this->serializer->encode($envelope);

        try {
            $payload = json_encode($encoded, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new TransportException('Failed to JSON-encode message for NATS transport', 0, $e);
        }

        $client->publish($subject, $payload);

        return $envelope;
    }

    private function buildSubjectFromEnvelope(Envelope $envelope): string
    {
        $messageClass = $envelope->getMessage()::class;
        $base = str_replace('\\', '.', $messageClass);

        if ($this->subjectPrefix) {
            return sprintf('%s.%s', $this->subjectPrefix, $base);
        }

        return $base;
    }
}
