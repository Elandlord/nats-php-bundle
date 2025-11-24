<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle;

use Elandlord\NatsPhpBundle\DependencyInjection\Compiler\NatsEventMapCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsPhpBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new NatsEventMapCompilerPass());
    }
}