<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Transport;

/**
 * Registry for keeping track of the created NatsTransportReceiver objects so it can be used to unsubscribe before
 * process end.
 *
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsReceiverRegistry
{
    /** @var NatsTransportReceiver[] */
    private array $receivers = [];

    public function register(NatsTransportReceiver $receiver): void
    {
        $this->receivers[] = $receiver;
    }

    /** @return NatsTransportReceiver[] */
    public function all(): array
    {
        return $this->receivers;
    }
}
