<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Event\Code;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
abstract class Test
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $file;

    /**
     * @psalm-param non-empty-string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function file(): string
    {
        return $this->file;
    }

    /**
     * @psalm-assert-if-true TestMethod $this
     */
    public function isTestMethod(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true Phpt $this
     */
    public function isPhpt(): bool
    {
        return false;
    }

    /**
     * @psalm-return non-empty-string
     */
    abstract public function id(): string;

    /**
     * @psalm-return non-empty-string
     */
    abstract public function name(): string;
}
