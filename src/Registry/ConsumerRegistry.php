<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Registry;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class ConsumerRegistry
{
    /**
     * @param array<string, array<string, mixed>> $definitions
     */
    public function __construct(
        private readonly array $definitions
    ) {}

    public function has(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    public function get(string $name): array
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('Unknown consumer "%s"', $name));
        }

        return $this->definitions[$name];
    }

    public function all(): array
    {
        return $this->definitions;
    }

    public function list(): array
    {
        return array_keys($this->definitions);
    }
}
