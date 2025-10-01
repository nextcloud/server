<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Portability;

use function array_change_key_case;
use function array_map;
use function array_reduce;
use function is_string;
use function rtrim;

use const CASE_LOWER;
use const CASE_UPPER;

final class Converter
{
    public const CASE_LOWER = CASE_LOWER;
    public const CASE_UPPER = CASE_UPPER;

    /** @var callable */
    private $convertNumeric;

    /** @var callable */
    private $convertAssociative;

    /** @var callable */
    private $convertOne;

    /** @var callable */
    private $convertAllNumeric;

    /** @var callable */
    private $convertAllAssociative;

    /** @var callable */
    private $convertFirstColumn;

    /**
     * @param bool                                   $convertEmptyStringToNull Whether each empty string should
     *                                                                         be converted to NULL
     * @param bool                                   $rightTrimString          Whether each string should right-trimmed
     * @param self::CASE_LOWER|self::CASE_UPPER|null $case                     Convert the case of the column names
     *                                                                         (one of {@see self::CASE_LOWER} and
     *                                                                         {@see self::CASE_UPPER})
     */
    public function __construct(bool $convertEmptyStringToNull, bool $rightTrimString, ?int $case)
    {
        $convertValue       = $this->createConvertValue($convertEmptyStringToNull, $rightTrimString);
        $convertNumeric     = $this->createConvertRow($convertValue, null);
        $convertAssociative = $this->createConvertRow($convertValue, $case);

        $this->convertNumeric     = $this->createConvert($convertNumeric, [self::class, 'id']);
        $this->convertAssociative = $this->createConvert($convertAssociative, [self::class, 'id']);
        $this->convertOne         = $this->createConvert($convertValue, [self::class, 'id']);

        $this->convertAllNumeric     = $this->createConvertAll($convertNumeric, [self::class, 'id']);
        $this->convertAllAssociative = $this->createConvertAll($convertAssociative, [self::class, 'id']);
        $this->convertFirstColumn    = $this->createConvertAll($convertValue, [self::class, 'id']);
    }

    /**
     * @param array<int,mixed>|false $row
     *
     * @return list<mixed>|false
     */
    public function convertNumeric($row)
    {
        return ($this->convertNumeric)($row);
    }

    /**
     * @param array<string,mixed>|false $row
     *
     * @return array<string,mixed>|false
     */
    public function convertAssociative($row)
    {
        return ($this->convertAssociative)($row);
    }

    /**
     * @param mixed|false $value
     *
     * @return mixed|false
     */
    public function convertOne($value)
    {
        return ($this->convertOne)($value);
    }

    /**
     * @param list<list<mixed>> $data
     *
     * @return list<list<mixed>>
     */
    public function convertAllNumeric(array $data): array
    {
        return ($this->convertAllNumeric)($data);
    }

    /**
     * @param list<array<string,mixed>> $data
     *
     * @return list<array<string,mixed>>
     */
    public function convertAllAssociative(array $data): array
    {
        return ($this->convertAllAssociative)($data);
    }

    /**
     * @param list<mixed> $data
     *
     * @return list<mixed>
     */
    public function convertFirstColumn(array $data): array
    {
        return ($this->convertFirstColumn)($data);
    }

    /**
     * @param T $value
     *
     * @return T
     *
     * @template T
     */
    private static function id($value)
    {
        return $value;
    }

    /**
     * @param T $value
     *
     * @return T|null
     *
     * @template T
     */
    private static function convertEmptyStringToNull($value)
    {
        if ($value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @param T $value
     *
     * @return T|string
     * @phpstan-return (T is string ? string : T)
     *
     * @template T
     */
    private static function rightTrimString($value)
    {
        if (! is_string($value)) {
            return $value;
        }

        return rtrim($value);
    }

    /**
     * Creates a function that will convert each individual value retrieved from the database
     *
     * @param bool $convertEmptyStringToNull Whether each empty string should be converted to NULL
     * @param bool $rightTrimString          Whether each string should right-trimmed
     *
     * @return callable|null The resulting function or NULL if no conversion is needed
     */
    private function createConvertValue(bool $convertEmptyStringToNull, bool $rightTrimString): ?callable
    {
        $functions = [];

        if ($convertEmptyStringToNull) {
            $functions[] = [self::class, 'convertEmptyStringToNull'];
        }

        if ($rightTrimString) {
            $functions[] = [self::class, 'rightTrimString'];
        }

        return $this->compose(...$functions);
    }

    /**
     * Creates a function that will convert each array-row retrieved from the database
     *
     * @param callable|null                          $function The function that will convert each value
     * @param self::CASE_LOWER|self::CASE_UPPER|null $case     Column name case
     *
     * @return callable|null The resulting function or NULL if no conversion is needed
     */
    private function createConvertRow(?callable $function, ?int $case): ?callable
    {
        $functions = [];

        if ($function !== null) {
            $functions[] = $this->createMapper($function);
        }

        if ($case !== null) {
            $functions[] = static function (array $row) use ($case): array {
                return array_change_key_case($row, $case);
            };
        }

        return $this->compose(...$functions);
    }

    /**
     * Creates a function that will be applied to the return value of Statement::fetch*()
     * or an identity function if no conversion is needed
     *
     * @param callable|null $function The function that will convert each tow
     * @param callable      $id       Identity function
     */
    private function createConvert(?callable $function, callable $id): callable
    {
        if ($function === null) {
            return $id;
        }

        return /**
                * @param T $value
                *
                * @phpstan-return (T is false ? false : T)
                *
                * @template T
                */
            static function ($value) use ($function) {
                if ($value === false) {
                    return false;
                }

                return $function($value);
            };
    }

    /**
     * Creates a function that will be applied to the return value of Statement::fetchAll*()
     * or an identity function if no transformation is required
     *
     * @param callable|null $function The function that will transform each value
     * @param callable      $id       Identity function
     */
    private function createConvertAll(?callable $function, callable $id): callable
    {
        if ($function === null) {
            return $id;
        }

        return $this->createMapper($function);
    }

    /**
     * Creates a function that maps each value of the array using the given function
     *
     * @param callable $function The function that maps each value of the array
     */
    private function createMapper(callable $function): callable
    {
        return static function (array $array) use ($function): array {
            return array_map($function, $array);
        };
    }

    /**
     * Creates a composition of the given set of functions
     *
     * @param callable(T):T ...$functions The functions to compose
     *
     * @return callable(T):T|null
     *
     * @template T
     */
    private function compose(callable ...$functions): ?callable
    {
        return array_reduce($functions, static function (?callable $carry, callable $item): callable {
            if ($carry === null) {
                return $item;
            }

            return /**
                    * @param T $value
                    *
                    * @return T
                    *
                    * @template T
                    */
                static function ($value) use ($carry, $item) {
                    return $item($carry($value));
                };
        });
    }
}
