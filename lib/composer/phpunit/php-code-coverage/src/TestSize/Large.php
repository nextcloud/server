<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test\TestSize;

/**
 * @psalm-immutable
 */
final class Large extends Known
{
    /**
     * @psalm-assert-if-true Large $this
     */
    public function isLarge(): bool
    {
        return true;
    }

    public function isGreaterThan(TestSize $other): bool
    {
        return !$other->isLarge();
    }

    public function asString(): string
    {
        return 'large';
    }
}
