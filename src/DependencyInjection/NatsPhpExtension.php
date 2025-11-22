<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsPhpExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('nats_php.config', $config);
        $container->setParameter('nats_php.consumer_definitions', $config['consumers'] ?? []);
        $container->setParameter('nats_php.publisher_definitions', $config['publishers'] ?? []);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'nats_php';
    }
}
