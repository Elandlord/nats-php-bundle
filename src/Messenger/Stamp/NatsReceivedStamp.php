<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Stamp;

use Basis\Nats\Message\Msg;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsReceivedStamp implements StampInterface
{
    public function __construct(
        public readonly Msg $message
    ) {}
}
