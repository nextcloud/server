<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Logging\TestDox;

use IteratorAggregate;

/**
 * @template-implements IteratorAggregate<int, TestResult>
 *
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class TestResultCollection implements IteratorAggregate
{
    /**
     * @psalm-var list<TestResult>
     */
    private readonly array $testResults;

    /**
     * @psalm-param list<TestResult> $testResults
     */
    public static function fromArray(array $testResults): self
    {
        return new self(...$testResults);
    }

    private function __construct(TestResult ...$testResults)
    {
        $this->testResults = $testResults;
    }

    /**
     * @psalm-return list<TestResult>
     */
    public function asArray(): array
    {
        return $this->testResults;
    }

    public function getIterator(): TestResultCollectionIterator
    {
        return new TestResultCollectionIterator($this);
    }
}
