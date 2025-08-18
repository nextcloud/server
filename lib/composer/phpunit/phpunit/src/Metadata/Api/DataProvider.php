<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Metadata\Api;

use const JSON_ERROR_NONE;
use const PREG_OFFSET_CAPTURE;
use function array_key_exists;
use function assert;
use function explode;
use function get_debug_type;
use function is_a;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function preg_match;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;
use PHPUnit\Event;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\TestData\MoreThanOneDataSetFromDataProviderException;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Framework\InvalidDataProviderException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\DataProvider as DataProviderMetadata;
use PHPUnit\Metadata\MetadataCollection;
use PHPUnit\Metadata\Parser\Registry as MetadataRegistry;
use PHPUnit\Metadata\TestWith;
use PHPUnit\Util\Reflection;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class DataProvider
{
    /**
     * @psalm-param class-string $className
     * @psalm-param non-empty-string $methodName
     *
     * @throws InvalidDataProviderException
     */
    public function providedData(string $className, string $methodName): ?array
    {
        $dataProvider = MetadataRegistry::parser()->forMethod($className, $methodName)->isDataProvider();
        $testWith     = MetadataRegistry::parser()->forMethod($className, $methodName)->isTestWith();

        if ($dataProvider->isEmpty() && $testWith->isEmpty()) {
            return $this->dataProvidedByTestWithAnnotation($className, $methodName);
        }

        if ($dataProvider->isNotEmpty()) {
            $data = $this->dataProvidedByMethods($className, $methodName, $dataProvider);
        } else {
            $data = $this->dataProvidedByMetadata($testWith);
        }

        if ($data === []) {
            throw new InvalidDataProviderException(
                'Empty data set provided by data provider',
            );
        }

        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                throw new InvalidDataProviderException(
                    sprintf(
                        'Data set %s is invalid',
                        is_int($key) ? '#' . $key : '"' . $key . '"',
                    ),
                );
            }
        }

        return $data;
    }

    /**
     * @psalm-param class-string $className
     * @psalm-param non-empty-string $methodName
     *
     * @throws InvalidDataProviderException
     */
    private function dataProvidedByMethods(string $className, string $methodName, MetadataCollection $dataProvider): array
    {
        $testMethod    = new Event\Code\ClassMethod($className, $methodName);
        $methodsCalled = [];
        $result        = [];

        foreach ($dataProvider as $_dataProvider) {
            assert($_dataProvider instanceof DataProviderMetadata);

            if (is_a($_dataProvider->className(), TestCase::class, true) &&
                str_starts_with($_dataProvider->methodName(), 'test')) {
                Event\Facade::emitter()->testRunnerTriggeredPhpunitWarning(
                    sprintf(
                        'The name of the data provider method %s::%s() used by test method %s::%s() begins with "test", therefore PHPUnit also treats it as a test method',
                        $_dataProvider->className(),
                        $_dataProvider->methodName(),
                        $className,
                        $methodName,
                    ),
                );
            }

            $dataProviderMethod = new Event\Code\ClassMethod($_dataProvider->className(), $_dataProvider->methodName());

            Event\Facade::emitter()->dataProviderMethodCalled(
                $testMethod,
                $dataProviderMethod,
            );

            $methodsCalled[] = $dataProviderMethod;

            try {
                $class  = new ReflectionClass($_dataProvider->className());
                $method = $class->getMethod($_dataProvider->methodName());
                $object = null;

                if (!$method->isPublic()) {
                    Event\Facade::emitter()->testTriggeredPhpunitDeprecation(
                        $this->valueObjectForTestMethodWithoutTestData(
                            $className,
                            $methodName,
                        ),
                        sprintf(
                            'Data Provider method %s::%s() is not public',
                            $_dataProvider->className(),
                            $_dataProvider->methodName(),
                        ),
                    );
                }

                if (!$method->isStatic()) {
                    Event\Facade::emitter()->testTriggeredPhpunitDeprecation(
                        $this->valueObjectForTestMethodWithoutTestData(
                            $className,
                            $methodName,
                        ),
                        sprintf(
                            'Data Provider method %s::%s() is not static',
                            $_dataProvider->className(),
                            $_dataProvider->methodName(),
                        ),
                    );

                    $object = $class->newInstanceWithoutConstructor();
                }

                if ($method->getNumberOfParameters() === 0) {
                    $data = $method->invoke($object);
                } else {
                    Event\Facade::emitter()->testTriggeredPhpunitDeprecation(
                        $this->valueObjectForTestMethodWithoutTestData(
                            $className,
                            $methodName,
                        ),
                        sprintf(
                            'Data Provider method %s::%s() expects an argument',
                            $_dataProvider->className(),
                            $_dataProvider->methodName(),
                        ),
                    );

                    $data = $method->invoke($object, $_dataProvider->methodName());
                }
            } catch (Throwable $e) {
                Event\Facade::emitter()->dataProviderMethodFinished(
                    $testMethod,
                    ...$methodsCalled,
                );

                throw new InvalidDataProviderException(
                    $e->getMessage(),
                    $e->getCode(),
                    $e,
                );
            }

            foreach ($data as $key => $value) {
                if (is_int($key)) {
                    $result[] = $value;
                } elseif (is_string($key)) {
                    if (array_key_exists($key, $result)) {
                        Event\Facade::emitter()->dataProviderMethodFinished(
                            $testMethod,
                            ...$methodsCalled,
                        );

                        throw new InvalidDataProviderException(
                            sprintf(
                                'The key "%s" has already been defined by a previous data provider',
                                $key,
                            ),
                        );
                    }

                    $result[$key] = $value;
                } else {
                    throw new InvalidDataProviderException(
                        sprintf(
                            'The key must be an integer or a string, %s given',
                            get_debug_type($key),
                        ),
                    );
                }
            }
        }

        Event\Facade::emitter()->dataProviderMethodFinished(
            $testMethod,
            ...$methodsCalled,
        );

        return $result;
    }

    private function dataProvidedByMetadata(MetadataCollection $testWith): array
    {
        $result = [];

        foreach ($testWith as $_testWith) {
            assert($_testWith instanceof TestWith);

            $result[] = $_testWith->data();
        }

        return $result;
    }

    /**
     * @psalm-param class-string $className
     *
     * @throws InvalidDataProviderException
     */
    private function dataProvidedByTestWithAnnotation(string $className, string $methodName): ?array
    {
        $docComment = (new ReflectionMethod($className, $methodName))->getDocComment();

        if ($docComment === false) {
            return null;
        }

        $docComment = str_replace("\r\n", "\n", $docComment);
        $docComment = preg_replace('/\n\s*\*\s?/', "\n", $docComment);
        $docComment = substr($docComment, 0, -1);
        $docComment = rtrim($docComment, "\n");

        if (!preg_match('/@testWith\s+/', $docComment, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $offset            = strlen($matches[0][0]) + (int) $matches[0][1];
        $annotationContent = substr($docComment, $offset);
        $data              = [];

        foreach (explode("\n", $annotationContent) as $candidateRow) {
            $candidateRow = trim($candidateRow);

            if ($candidateRow === '' || $candidateRow[0] !== '[') {
                break;
            }

            $dataSet = json_decode($candidateRow, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidDataProviderException(
                    'The data set for the @testWith annotation cannot be parsed: ' . json_last_error_msg(),
                );
            }

            $data[] = $dataSet;
        }

        if (!$data) {
            throw new InvalidDataProviderException(
                'The data set for the @testWith annotation cannot be parsed.',
            );
        }

        return $data;
    }

    /**
     * @psalm-param class-string $className
     * @psalm-param non-empty-string $methodName
     *
     * @throws MoreThanOneDataSetFromDataProviderException
     */
    private function valueObjectForTestMethodWithoutTestData(string $className, string $methodName): TestMethod
    {
        $location = Reflection::sourceLocationFor($className, $methodName);

        return new TestMethod(
            $className,
            $methodName,
            $location['file'],
            $location['line'],
            Event\Code\TestDoxBuilder::fromClassNameAndMethodName(
                $className,
                $methodName,
            ),
            MetadataRegistry::parser()->forClassAndMethod(
                $className,
                $methodName,
            ),
            TestDataCollection::fromArray([]),
        );
    }
}
