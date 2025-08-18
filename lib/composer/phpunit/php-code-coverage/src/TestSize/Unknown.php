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
final class Unknown extends TestSize
{
    /**
     * @psalm-assert-if-true Unknown $this
     */
    public function isUnknown(): bool
    {
        return true;
    }

    public function asString(): string
    {
        return 'unknown';
    }
}
