<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Transport;

use Basis\Nats\Message\Msg;
use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhp\Exception\InvalidEventEnvelopeException;
use JsonException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Throwable;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsTransportReceiver implements ReceiverInterface
{
    /** @var array<string, Msg> */
    protected array $messages = [];

    public function __construct(
        protected readonly NatsConnection $connection,
        protected readonly SerializerInterface $serializer,
        protected readonly string $stream,
        protected readonly string $consumer,
        protected readonly int $timeoutMs = 1000
    ) {
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function get(): iterable
    {
        $client   = $this->connection->getClient();
        $stream   = $client->getApi()->getStream($this->stream);
        $consumer = $stream->getConsumer($this->consumer);
        $queue    = $consumer->getQueue();

        while (true) {
            $message = $queue->next($this->timeoutMs);
            $payload = $message->payload->body;

            try {
                $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($data) || !isset($data['body'], $data['headers'])) {
                    throw new InvalidEventEnvelopeException();
                }

                $envelope = $this->serializer->decode($data);

            } catch (Throwable $exception) {
                $message->nack(1.0);
                throw $exception;
            }

            $hash = spl_object_hash($envelope);
            $this->messages[$hash] = $message;

            yield $envelope;
        }
    }

    public function ack(Envelope $envelope): void
    {
        $hash = spl_object_hash($envelope);
        if (!isset($this->messages[$hash])) {
            return;
        }

        $this->messages[$hash]->ack();
        unset($this->messages[$hash]);
    }

    public function reject(Envelope $envelope): void
    {
        $hash = spl_object_hash($envelope);
        if (!isset($this->messages[$hash])) {
            return;
        }

        $this->messages[$hash]->nack(1.0);
        unset($this->messages[$hash]);
    }
}