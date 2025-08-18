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

use function assert;
use function is_int;
use function sprintf;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Metadata\MetadataCollection;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class TestMethod extends Test
{
    /**
     * @psalm-var class-string
     */
    private readonly string $className;

    /**
     * @psalm-var non-empty-string
     */
    private readonly string $methodName;

    /**
     * @psalm-var non-negative-int
     */
    private readonly int $line;
    private readonly TestDox $testDox;
    private readonly MetadataCollection $metadata;
    private readonly TestDataCollection $testData;

    /**
     * @psalm-param class-string $className
     * @psalm-param non-empty-string $methodName
     * @psalm-param non-empty-string $file
     * @psalm-param non-negative-int $line
     */
    public function __construct(string $className, string $methodName, string $file, int $line, TestDox $testDox, MetadataCollection $metadata, TestDataCollection $testData)
    {
        parent::__construct($file);

        $this->className  = $className;
        $this->methodName = $methodName;
        $this->line       = $line;
        $this->testDox    = $testDox;
        $this->metadata   = $metadata;
        $this->testData   = $testData;
    }

    /**
     * @psalm-return class-string
     */
    public function className(): string
    {
        return $this->className;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function methodName(): string
    {
        return $this->methodName;
    }

    /**
     * @psalm-return non-negative-int
     */
    public function line(): int
    {
        return $this->line;
    }

    public function testDox(): TestDox
    {
        return $this->testDox;
    }

    public function metadata(): MetadataCollection
    {
        return $this->metadata;
    }

    public function testData(): TestDataCollection
    {
        return $this->testData;
    }

    /**
     * @psalm-assert-if-true TestMethod $this
     */
    public function isTestMethod(): bool
    {
        return true;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function id(): string
    {
        $buffer = $this->className . '::' . $this->methodName;

        if ($this->testData()->hasDataFromDataProvider()) {
            $buffer .= '#' . $this->testData->dataFromDataProvider()->dataSetName();
        }

        return $buffer;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function nameWithClass(): string
    {
        return $this->className . '::' . $this->name();
    }

    /**
     * @psalm-return non-empty-string
     */
    public function name(): string
    {
        if (!$this->testData->hasDataFromDataProvider()) {
            return $this->methodName;
        }

        $dataSetName = $this->testData->dataFromDataProvider()->dataSetName();

        if (is_int($dataSetName)) {
            $dataSetName = sprintf(
                ' with data set #%d',
                $dataSetName,
            );
        } else {
            $dataSetName = sprintf(
                ' with data set "%s"',
                $dataSetName,
            );
        }

        return $this->methodName . $dataSetName;
    }
}
