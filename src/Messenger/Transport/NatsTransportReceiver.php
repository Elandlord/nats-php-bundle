<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Transport;

use Basis\Nats\Consumer\Consumer;
use Basis\Nats\Message\Msg;
use CloudEvents\Exceptions\InvalidPayloadSyntaxException;
use CloudEvents\Exceptions\MissingAttributeException;
use CloudEvents\Exceptions\UnsupportedSpecVersionException;
use CloudEvents\Serializers\JsonDeserializer;
use CloudEvents\V1\CloudEventInterface;
use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhpBundle\Messenger\Stamp\NatsReceivedStamp;
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
    public const DEFAULT_TIMEOUT_MS = 1000;
    public const DEFAULT_MAX_DELIVER = 3;
    public const DEFAULT_ACK_WAIT_MS = 10_000;

    /**
     * @var array<string, class-string> $eventMap
     */
    public function __construct(
        protected readonly NatsConnection      $connection,
        protected readonly SerializerInterface $serializer,
        protected readonly string              $stream,
        protected readonly string              $consumer,
        protected readonly ?string             $subjectFilter = null,
        protected readonly int                 $maxDeliver = self::DEFAULT_MAX_DELIVER,
        protected readonly int                 $ackWaitMs = self::DEFAULT_ACK_WAIT_MS,
        protected readonly int                 $timeoutMs = self::DEFAULT_TIMEOUT_MS,
        protected readonly array               $eventMap = [],
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function get(): iterable
    {
        $consumer = $this->getOrCreateConsumer();
        $queue = $consumer->getQueue();

        $message = $queue->next($this->timeoutMs);

        if (!$this->shouldProcess($message)) {
            return;
        }

        try {
            $envelope = $this->buildEnvelopeFromMessage($message);
        } catch (Throwable $exception) {
            $this->onProcessingError($exception, $message);
            throw $exception;
        }

        $envelope = $envelope->with(new NatsReceivedStamp($message));
        yield $envelope;
    }

    /**
     * @throws InvalidPayloadSyntaxException
     * @throws UnsupportedSpecVersionException
     * @throws MissingAttributeException
     */
    protected function buildEnvelopeFromMessage(Msg $message): Envelope
    {
        $cloudEvent = JsonDeserializer::create()->deserializeStructured($message->payload->body);

        if (!$cloudEvent instanceof CloudEventInterface) {
            throw new TransportException('Only CloudEvent v1 is supported.');
        }

        $messageClass = $this->eventMap[$cloudEvent->getType()] ?? null;

        if ($messageClass) {
            $data = $cloudEvent->getData();

            if (!is_array($data)) {
                $data = (array) $data;
            }

            $data = array_merge([
                'source' => $cloudEvent->getSource(),
            ], $data);

            $dto = $this->hydrateMessage($messageClass, $data);

            return new Envelope($dto);
        }

        return new Envelope($cloudEvent);
    }

    protected function hydrateMessage(string $messageClass, array $body): object
    {
        try {
            return new $messageClass(...$body);
        } catch (Throwable $exception) {
            throw new TransportException(
                sprintf('Failed to hydrate "%s" from NATS body keys [%s].',
                    $messageClass,
                    implode(', ', array_keys($body))
                ),
                0,
                $exception
            );
        }
    }

    protected function onProcessingError(Throwable $exception, Msg $message): void
    {
        // Left empty on purpose.
    }

    protected function shouldProcess(mixed $message): bool
    {
        return $message instanceof Msg && $message->replyTo !== null;
    }

    public function ack(Envelope $envelope): void
    {
        $stamp = $envelope->last(NatsReceivedStamp::class);

        if ($stamp === null) {
            return;
        }

        $message = $stamp->message;

        if ($message->replyTo !== null) {
            $message->ack();
        }
    }

    public function reject(Envelope $envelope): void
    {
        $stamp = $envelope->last(NatsReceivedStamp::class);

        if ($stamp === null) {
            return;
        }

        $message = $stamp->message;

        if ($message->replyTo !== null) {
            $message->nack(1.0);
        }
    }

    protected function getOrCreateConsumer(): Consumer
    {
        $client = $this->connection->getClient();

        $stream = $client->getApi()->getStream($this->stream);

        $consumer = $stream->getConsumer($this->consumer);
        $config = $consumer->getConfiguration();

        if ($this->subjectFilter !== null) {
            $config->setSubjectFilter($this->subjectFilter);
        }

        $config->setMaxDeliver($this->maxDeliver);
        $config->setAckWait($this->ackWaitMs);

        return $consumer->create();
    }
}
