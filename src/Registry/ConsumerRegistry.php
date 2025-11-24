<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Registry;

use InvalidArgumentException;

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
        protected readonly array $definitions
    )
    {
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    /**
     * @return array{key: string, stream: string, subject_filter?: ?string, max_deliver?: int, ack_wait_ms?: int}
     */
    public function get(string $key): array
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException(sprintf('Unknown consumer "%s"', $key));
        }

        return [
            'key' => $key,
            ...$this->definitions[$key],
        ];
    }

    /**
     * @return array<string, array{key: string} & array<string,mixed>>
     */
    public function all(): array
    {
        $all = [];
        foreach ($this->definitions as $key => $definition) {
            $all[$key] = [
                'key' => $key,
                ...$definition,
            ];
        }
        return $all;
    }

    public function list(): array
    {
        return array_keys($this->definitions);
    }
}