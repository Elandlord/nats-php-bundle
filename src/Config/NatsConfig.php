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
        return (string)($this->config['host'] ?? '');
    }

    public function getPort(): int
    {
        return (int)($this->config['port'] ?? 4222);
    }

    public function getUser(): ?string
    {
        $user = $this->config['user'] ?? null;
        return ($user === '' ? null : $user);
    }

    public function getPass(): ?string
    {
        $pass = $this->config['pass'] ?? null;
        return ($pass === '' ? null : $pass);
    }

    public function getReconnect(): bool
    {
        return (bool)($this->config['reconnect'] ?? true);
    }

    public function getPedantic(): bool
    {
        return (bool)($this->config['pedantic'] ?? false);
    }

    public function getDelay(): float
    {
        return (float)($this->config['delay'] ?? 0.01);
    }

    public function getTlsCaFile(): ?string
    {
        $ca = $this->config['tls_ca_file'] ?? null;
        return ($ca === '' ? null : $ca);
    }

    public function getTlsHandshakeFirst(): ?bool
    {
        if (!array_key_exists('tls_handshake_first', $this->config)) {
            return null;
        }
        return (bool)$this->config['tls_handshake_first'];
    }
}
