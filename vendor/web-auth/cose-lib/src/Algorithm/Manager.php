<?php

declare(strict_types=1);

namespace Cose\Algorithm;

use InvalidArgumentException;
use function array_key_exists;

final class Manager
{
    /**
     * @var array<int, Algorithm>
     */
    private array $algorithms = [];

    public static function create(): self
    {
        return new self();
    }

    public function add(Algorithm ...$algorithms): self
    {
        foreach ($algorithms as $algorithm) {
            $identifier = $algorithm::identifier();
            $this->algorithms[$identifier] = $algorithm;
        }

        return $this;
    }

    /**
     * @return iterable<int>
     */
    public function list(): iterable
    {
        yield from array_keys($this->algorithms);
    }

    /**
     * @return iterable<int, Algorithm>
     */
    public function all(): iterable
    {
        yield from $this->algorithms;
    }

    public function has(int $identifier): bool
    {
        return array_key_exists($identifier, $this->algorithms);
    }

    public function get(int $identifier): Algorithm
    {
        if (! $this->has($identifier)) {
            throw new InvalidArgumentException('Unsupported algorithm');
        }

        return $this->algorithms[$identifier];
    }
}
