<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Transport;

use Basis\Nats\Consumer\Consumer;
use Basis\Nats\Message\Msg;
use Basis\Nats\Stream\Stream;
use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhpBundle\Messenger\Message\RawNatsEvent;
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
    public const DEFAULT_TIMEOUT_MS   = 1000;
    public const DEFAULT_MAX_DELIVER  = 3;
    public const DEFAULT_ACK_WAIT_MS  = 10_000;

    public const EVENT_NAME_KEY = 'eventName';
    public const BODY_KEY       = 'body';
    public const HEADERS_KEY    = 'headers';

    /**
     * @var array<string, Msg>
     */
    protected array $messages = [];

    public function __construct(
        protected readonly NatsConnection $connection,
        protected readonly SerializerInterface $serializer,
        protected readonly string $stream,
        protected readonly string $consumer,
        protected readonly ?string $subjectFilter = null,
        protected readonly int $maxDeliver = self::DEFAULT_MAX_DELIVER,
        protected readonly int $ackWaitMs = self::DEFAULT_ACK_WAIT_MS,
        protected readonly int $timeoutMs = self::DEFAULT_TIMEOUT_MS,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function get(): iterable
    {
        $consumer = $this->getOrCreateConsumer();
        $queue    = $consumer->getQueue();

        $message = $queue->next($this->timeoutMs);

        if (!$this->shouldProcess($message)) {
            return;
        }

        try {
            $decoded  = $this->decodePayload($message->payload->body);
            $envelope = $this->buildEnvelopeFromDecoded($decoded, $message);
        } catch (Throwable $exception) {
            $this->onProcessingError($exception, $message);
            throw $exception;
        }

        $hash = spl_object_hash($envelope);
        $this->messages[$hash] = $message;

        yield $envelope;
    }

    protected function buildEnvelopeFromDecoded(array $data, Msg $message): Envelope
    {
        if (isset($data[self::EVENT_NAME_KEY], $data[self::BODY_KEY]) && is_string($data[self::EVENT_NAME_KEY])) {
            $body = $data[self::BODY_KEY];

            if (!is_array($body)) {
                $body = (array)$body;
            }

            return new Envelope(new RawNatsEvent(
                $data[self::EVENT_NAME_KEY],
                $body
            ));
        }

        if (isset($data[self::BODY_KEY], $data[self::HEADERS_KEY])) {
            return $this->serializer->decode($data);
        }

        $eventName = $message->subject;
        return new Envelope(new RawNatsEvent($eventName, $data));
    }

    protected function onProcessingError(Throwable $exception, Msg $message): void
    {
        if ($message->replyTo !== null) {
            $message->nack(1.0);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodePayload(string $payload): array
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new TransportException('Invalid JSON payload received from NATS.', 0, $exception);
        }

        if (!is_array($data)) {
            throw new TransportException('Invalid JSON payload from NATS (not an object).');
        }

        return $data;
    }

    protected function shouldProcess(mixed $message): bool
    {
        return $message instanceof Msg && $message->replyTo !== null;
    }

    public function ack(Envelope $envelope): void
    {
        $hash = spl_object_hash($envelope);
        if (!isset($this->messages[$hash])) {
            return;
        }

        $message = $this->messages[$hash];
        if ($message->replyTo !== null) {
            $message->ack();
        }

        unset($this->messages[$hash]);
    }

    public function reject(Envelope $envelope): void
    {
        $hash = spl_object_hash($envelope);
        if (!isset($this->messages[$hash])) {
            return;
        }

        $message = $this->messages[$hash];
        if ($message->replyTo !== null) {
            $message->nack(1.0);
        }

        unset($this->messages[$hash]);
    }

    protected function getOrCreateConsumer(): Consumer
    {
        $client = $this->connection->getClient();

        $stream = $client->getApi()->getStream($this->stream);

        $consumer = $stream->getConsumer($this->consumer);
        $config   = $consumer->getConfiguration();

        if ($this->subjectFilter !== null) {
            $config->setSubjectFilter($this->subjectFilter);
        }

        $config->setMaxDeliver($this->maxDeliver);
        $config->setAckWait($this->ackWaitMs);

        return $consumer->create();
    }
}
