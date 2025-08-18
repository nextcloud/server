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
final class TestSuiteForTestClass extends TestSuite
{
    /**
     * @psalm-var class-string
     */
    private readonly string $className;
    private readonly string $file;
    private readonly int $line;

    /**
     * @psalm-param class-string $name
     */
    public function __construct(string $name, int $size, TestCollection $tests, string $file, int $line)
    {
        parent::__construct($name, $size, $tests);

        $this->className = $name;
        $this->file      = $file;
        $this->line      = $line;
    }

    /**
     * @psalm-return class-string
     */
    public function className(): string
    {
        return $this->className;
    }

    public function file(): string
    {
        return $this->file;
    }

    public function line(): int
    {
        return $this->line;
    }

    /**
     * @psalm-assert-if-true TestSuiteForTestClass $this
     */
    public function isForTestClass(): bool
    {
        return true;
    }
}
