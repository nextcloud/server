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

use function explode;
use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestCollection;
use PHPUnit\Event\RuntimeException;
use PHPUnit\Framework\DataProviderTestSuite;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite as FrameworkTestSuite;
use PHPUnit\Runner\PhptTestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class TestSuiteBuilder
{
    /**
     * @throws RuntimeException
     */
    public static function from(FrameworkTestSuite $testSuite): TestSuite
    {
        $tests = [];

        self::process($testSuite, $tests);

        if ($testSuite instanceof DataProviderTestSuite) {
            [$className, $methodName] = explode('::', $testSuite->name());

            try {
                $reflector = new ReflectionMethod($className, $methodName);

                return new TestSuiteForTestMethodWithDataProvider(
                    $testSuite->name(),
                    $testSuite->count(),
                    TestCollection::fromArray($tests),
                    $className,
                    $methodName,
                    $reflector->getFileName(),
                    $reflector->getStartLine(),
                );
                // @codeCoverageIgnoreStart
            } catch (ReflectionException $e) {
                throw new RuntimeException(
                    $e->getMessage(),
                    $e->getCode(),
                    $e,
                );
            }
            // @codeCoverageIgnoreEnd
        }

        if ($testSuite->isForTestClass()) {
            try {
                $reflector = new ReflectionClass($testSuite->name());

                return new TestSuiteForTestClass(
                    $testSuite->name(),
                    $testSuite->count(),
                    TestCollection::fromArray($tests),
                    $reflector->getFileName(),
                    $reflector->getStartLine(),
                );
                // @codeCoverageIgnoreStart
            } catch (ReflectionException $e) {
                throw new RuntimeException(
                    $e->getMessage(),
                    $e->getCode(),
                    $e,
                );
            }
            // @codeCoverageIgnoreEnd
        }

        return new TestSuiteWithName(
            $testSuite->name(),
            $testSuite->count(),
            TestCollection::fromArray($tests),
        );
    }

    /**
     * @psalm-param list<Test> $tests
     */
    private static function process(FrameworkTestSuite $testSuite, array &$tests): void
    {
        foreach ($testSuite->getIterator() as $test) {
            if ($test instanceof FrameworkTestSuite) {
                self::process($test, $tests);

                continue;
            }

            if ($test instanceof TestCase || $test instanceof PhptTestCase) {
                $tests[] = $test->valueObjectForEvents();
            }
        }
    }
}
