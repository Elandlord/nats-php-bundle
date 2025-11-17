<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\DependencyInjection;

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

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('host')->defaultValue('localhost')->end()
            ->integerNode('port')->defaultValue(4222)->end()
            ->scalarNode('user')->defaultNull()->end()
            ->scalarNode('pass')->defaultNull()->end()
            ->booleanNode('reconnect')->defaultTrue()->end()
            ->booleanNode('pedantic')->defaultFalse()->end()
            ->floatNode('delay')->defaultValue(0.01)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
