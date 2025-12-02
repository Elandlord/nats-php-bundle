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
        $options = $this->getDefaultConfig();
        $options = $this->addTlsSettings($options);

        $configuration = new Configuration($options);

        $configuration->setDelay($this->config->getDelay());
        return NatsConnection::fromConfiguration($configuration);
    }

    protected function getDefaultConfig(): array
    {
        return [
            'host' => $this->config->getHost(),
            'port' => $this->config->getPort(),
            'user' => $this->config->getUser(),
            'pass' => $this->config->getPass(),
            'reconnect' => $this->config->getReconnect(),
            'pedantic' => $this->config->getPedantic(),
        ];
    }

    protected function addTlsSettings(array $options): array
    {
        $tlsCaFile = $this->config->getTlsCaFile();

        if ($tlsCaFile === null) {
            return $options;
        }

        $options['tls_ca_file'] = $tlsCaFile;
        $tlsHandshakeFirst = $this->config->getTlsHandshakeFirst();

        if ($tlsHandshakeFirst !== null) {
            $options['tls_handshake_first'] = $tlsHandshakeFirst;
        }

        return $options;
    }
}
