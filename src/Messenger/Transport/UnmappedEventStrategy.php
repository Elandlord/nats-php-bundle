<?php

declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Messenger\Transport;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
enum UnmappedEventStrategy: string
{
    case Ack = 'ack';
    case PassThrough = 'pass_through';
}
