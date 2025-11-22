<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Connection;

use Basis\Nats\Configuration;
use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhpBundle\Config\NatsConfig;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsConnectionFactory
{
    public function __construct(
        protected NatsConfig $config
    )
    {
    }

    public function create(): NatsConnection
    {
        $configuration = new Configuration([
            'host' => $this->config->getHost(),
            'port' => $this->config->getPort(),
            'user' => $this->config->getUser(),
            'pass' => $this->config->getPass(),
            'reconnect' => $this->config->getReconnect(),
            'pedantic' => $this->config->getPedantic(),
        ]);

        $configuration->setDelay($this->config->getDelay());

        return NatsConnection::fromConfiguration($configuration);
    }
}
