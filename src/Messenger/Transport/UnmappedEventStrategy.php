<?php

declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Transport;

/**
 * @copyright    2026, Eric Landheer
 * @license      MIT License
 */
enum UnmappedEventStrategy: string
{
    case ACK = 'ack';
    case PASSTHROUGH = 'pass_through';
}
