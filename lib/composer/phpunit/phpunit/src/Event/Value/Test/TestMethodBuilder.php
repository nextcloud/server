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

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const DEBUG_BACKTRACE_PROVIDE_OBJECT;
use function assert;
use function debug_backtrace;
use function is_numeric;
use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Event\TestData\DataFromDataProvider;
use PHPUnit\Event\TestData\DataFromTestDependency;
use PHPUnit\Event\TestData\MoreThanOneDataSetFromDataProviderException;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\Parser\Registry as MetadataRegistry;
use PHPUnit\Util\Exporter;
use PHPUnit\Util\Reflection;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class TestMethodBuilder
{
    /**
     * @throws MoreThanOneDataSetFromDataProviderException
     */
    public static function fromTestCase(TestCase $testCase): TestMethod
    {
        $methodName = $testCase->name();

        assert(!empty($methodName));

        $location = Reflection::sourceLocationFor($testCase::class, $methodName);

        return new TestMethod(
            $testCase::class,
            $methodName,
            $location['file'],
            $location['line'],
            TestDoxBuilder::fromTestCase($testCase),
            MetadataRegistry::parser()->forClassAndMethod($testCase::class, $methodName),
            self::dataFor($testCase),
        );
    }

    /**
     * @throws NoTestCaseObjectOnCallStackException
     */
    public static function fromCallStack(): TestMethod
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            if (isset($frame['object']) && $frame['object'] instanceof TestCase) {
                return $frame['object']->valueObjectForEvents();
            }
        }

        throw new NoTestCaseObjectOnCallStackException;
    }

    /**
     * @throws MoreThanOneDataSetFromDataProviderException
     */
    private static function dataFor(TestCase $testCase): TestDataCollection
    {
        $testData = [];

        if ($testCase->usesDataProvider()) {
            $dataSetName = $testCase->dataName();

            if (is_numeric($dataSetName)) {
                $dataSetName = (int) $dataSetName;
            }

            $testData[] = DataFromDataProvider::from(
                $dataSetName,
                Exporter::export($testCase->providedData(), EventFacade::emitter()->exportsObjects()),
                $testCase->dataSetAsStringWithData(),
            );
        }

        if ($testCase->hasDependencyInput()) {
            $testData[] = DataFromTestDependency::from(
                Exporter::export($testCase->dependencyInput(), EventFacade::emitter()->exportsObjects()),
            );
        }

        return TestDataCollection::fromArray($testData);
    }
}
