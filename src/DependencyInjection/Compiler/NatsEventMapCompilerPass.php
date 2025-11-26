<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\DependencyInjection\Compiler;

use CloudEvents\V1\CloudEventInterface;
use Elandlord\NatsPhp\Contract\Message\EventMessageInterface;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Throwable;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsEventMapCompilerPass implements CompilerPassInterface
{
    protected const MESSAGE_HANDLER_TAG = 'messenger.message_handler';

    /**
     * @return array<string, array>
     */
    protected function getMessageHandlers(ContainerBuilder $container): array
    {
        return $container->findTaggedServiceIds(self::MESSAGE_HANDLER_TAG);
    }

    /**
     * @throws ReflectionException
     */
    protected function handleMessageHandler(ContainerBuilder $container, string $id): ?array
    {
        $class = $container->getDefinition($id)->getClass();
        if (!$class || !class_exists($class)) {
            return null;
        }

        $reflectionClass = new ReflectionClass($class);

        if (!$reflectionClass->hasMethod('__invoke')) {
            return null;
        }

        try {
            $reflectionType = $this->isTypeAllowed($reflectionClass);
        } catch (Throwable) {
            return null;
        }

        $messageClass = $reflectionType->getName();
        if (!is_subclass_of($messageClass, CloudEventInterface::class)) {
            return null;
        }

        $eventName = (new ReflectionClass($messageClass))
            ->getConstant('EVENT_NAME');

        if (is_string($eventName) && $eventName !== '') {
            return [$eventName, $messageClass];
        }

        return null;
    }

    /**
     * @throws ReflectionException
     */
    protected function isTypeAllowed(ReflectionClass $reflectionClass): ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType
    {
        $invoke = $reflectionClass->getMethod('__invoke');
        $param = $invoke->getParameters()[0] ?? null;
        $type = $param?->getType();

        if (!$type || $type->isBuiltin()) {
            throw new InvalidArgumentException();
        }

        return $type;
    }

    public function process(ContainerBuilder $container): void
    {
        $map = [];

        $messageHandlers = $this->getMessageHandlers($container);

        foreach ($messageHandlers as $id => $tags) {
            $eventName = null;
            $messageClass = null;

            try {
                [$eventName, $messageClass] = $this->handleMessageHandler($container, $id);
            } catch (Throwable) {
                // Left empty on purpose
            }

            if ($eventName === null || $messageClass === null) {
                continue;
            }

            $map[$eventName] = $messageClass;
        }

        $container->setParameter('nats_php.event_map', $map);
    }
}