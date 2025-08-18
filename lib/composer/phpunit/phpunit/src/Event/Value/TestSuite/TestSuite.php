<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Event\TestSuite;

use PHPUnit\Event\Code\TestCollection;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
abstract class TestSuite
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $name;
    private readonly int $count;
    private readonly TestCollection $tests;

    /**
     * @psalm-param non-empty-string $name
     */
    public function __construct(string $name, int $size, TestCollection $tests)
    {
        $this->name  = $name;
        $this->count = $size;
        $this->tests = $tests;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function tests(): TestCollection
    {
        return $this->tests;
    }

    /**
     * @psalm-assert-if-true TestSuiteWithName $this
     */
    public function isWithName(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true TestSuiteForTestClass $this
     */
    public function isForTestClass(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true TestSuiteForTestMethodWithDataProvider $this
     */
    public function isForTestMethodWithDataProvider(): bool
    {
        return false;
    }
}
