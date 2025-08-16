<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test\TestStatus;

/**
 * @psalm-immutable
 */
abstract class TestStatus
{
    public static function unknown(): self
    {
        return new Unknown;
    }

    public static function success(): self
    {
        return new Success;
    }

    public static function failure(): self
    {
        return new Failure;
    }

    /**
     * @psalm-assert-if-true Known $this
     */
    public function isKnown(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true Unknown $this
     */
    public function isUnknown(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true Success $this
     */
    public function isSuccess(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true Failure $this
     */
    public function isFailure(): bool
    {
        return false;
    }

    abstract public function asString(): string;
}
