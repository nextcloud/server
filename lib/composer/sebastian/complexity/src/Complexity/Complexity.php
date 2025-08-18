<?php declare(strict_types=1);
/*
 * This file is part of sebastian/complexity.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Complexity;

use function str_contains;

/**
 * @psalm-immutable
 */
final class Complexity
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $name;

    /**
     * @psalm-var positive-int
     */
    private int $cyclomaticComplexity;

    /**
     * @psalm-param non-empty-string $name
     * @psalm-param positive-int $cyclomaticComplexity
     */
    public function __construct(string $name, int $cyclomaticComplexity)
    {
        $this->name                 = $name;
        $this->cyclomaticComplexity = $cyclomaticComplexity;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @psalm-return positive-int
     */
    public function cyclomaticComplexity(): int
    {
        return $this->cyclomaticComplexity;
    }

    public function isFunction(): bool
    {
        return !$this->isMethod();
    }

    public function isMethod(): bool
    {
        return str_contains($this->name, '::');
    }
}
