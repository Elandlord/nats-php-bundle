<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Config;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsConfig
{
    public function __construct(
        protected array $config
    ) {
    }

    public function getHost(): string
    {
        return (string)$this->config['host'];
    }

    public function getPort(): int
    {
        return (int)$this->config['port'];
    }

    public function getUser(): ?string
    {
        return $this->config['user'];
    }

    public function getPass(): ?string
    {
        return $this->config['pass'];
    }

    public function getReconnect(): bool
    {
        return (bool)$this->config['reconnect'];
    }

    public function getPedantic(): bool
    {
        return (bool)$this->config['pedantic'];
    }

    public function getDelay(): float
    {
        return (float)$this->config['delay'];
    }
}
