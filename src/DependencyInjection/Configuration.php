<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\DependencyInjection;

use Elandlord\NatsPhp\Consumer\AbstractEventConsumer;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nats_php');
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->scalarNode('host')->defaultValue('localhost')->end()
                ->integerNode('port')->defaultValue(4222)->end()
                ->scalarNode('user')->defaultNull()->end()
                ->scalarNode('pass')->defaultNull()->end()
                ->booleanNode('reconnect')->defaultTrue()->end()
                ->booleanNode('pedantic')->defaultFalse()->end()
                ->floatNode('delay')->defaultValue(0.01)->end()

                ->arrayNode('consumers')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('stream')->isRequired()->end()
                            ->scalarNode('subject_filter')->defaultNull()->end()
                            ->integerNode('max_deliver')->defaultValue(AbstractEventConsumer::DEFAULT_MAX_DELIVER)->end()
                            ->integerNode('ack_wait_ms')->defaultValue(AbstractEventConsumer::DEFAULT_ACK_WAIT_MS)->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('publishers')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('stream')->isRequired()->end()
                            ->scalarNode('subject_prefix')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
