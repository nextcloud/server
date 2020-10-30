<?php

/**
 * provides type inference and auto-completion for magic static methods of Assert.
 */

namespace Webmozart\Assert;

use ArrayAccess;
use Closure;
use Countable;
use InvalidArgumentException;
use Throwable;

interface Mixin
{
    /**
     * @psalm-pure
     * @psalm-assert null|string $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrString($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<string> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allString($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|non-empty-string $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrStringNotEmpty($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<non-empty-string> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allStringNotEmpty($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|int $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrInteger($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<int> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allInteger($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|numeric $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIntegerish($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<numeric> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIntegerish($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|float $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrFloat($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<float> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allFloat($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|numeric $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNumeric($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<numeric> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNumeric($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|int $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNatural($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<int> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNatural($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|bool $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrBoolean($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<bool> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allBoolean($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|scalar $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrScalar($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<scalar> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allScalar($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|object $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrObject($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<object> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allObject($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|resource $value
     *
     * @param mixed       $value
     * @param string|null $type    type of resource this should be. @see https://www.php.net/manual/en/function.get-resource-type.php
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrResource($value, $type = null, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<resource> $value
     *
     * @param mixed       $value
     * @param string|null $type    type of resource this should be. @see https://www.php.net/manual/en/function.get-resource-type.php
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function allResource($value, $type = null, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|callable $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsCallable($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<callable> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsCallable($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|array $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsArray($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<array> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsArray($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|iterable $value
     *
     * @deprecated use "isIterable" or "isInstanceOf" instead
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsTraversable($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<iterable> $value
     *
     * @deprecated use "isIterable" or "isInstanceOf" instead
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsTraversable($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|array|ArrayAccess $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsArrayAccessible($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<array|ArrayAccess> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsArrayAccessible($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|countable $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsCountable($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<countable> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsCountable($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|iterable $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsIterable($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<iterable> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsIterable($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     * @psalm-assert null|ExpectedType $value
     *
     * @param mixed         $value
     * @param string|object $class
     * @param string        $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsInstanceOf($value, $class, $message = '');

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     * @psalm-assert iterable<ExpectedType> $value
     *
     * @param mixed         $value
     * @param string|object $class
     * @param string        $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsInstanceOf($value, $class, $message = '');

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     *
     * @param mixed         $value
     * @param string|object $class
     * @param string        $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNotInstanceOf($value, $class, $message = '');

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     *
     * @param mixed         $value
     * @param string|object $class
     * @param string        $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNotInstanceOf($value, $class, $message = '');

    /**
     * @psalm-pure
     * @psalm-param array<class-string> $classes
     *
     * @param mixed                $value
     * @param array<object|string> $classes
     * @param string               $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsInstanceOfAny($value, $classes, $message = '');

    /**
     * @psalm-pure
     * @psalm-param array<class-string> $classes
     *
     * @param mixed                $value
     * @param array<object|string> $classes
     * @param string               $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsInstanceOfAny($value, $classes, $message = '');

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     * @psalm-assert null|ExpectedType|class-string<ExpectedType> $value
     *
     * @param null|object|string $value
     * @param string             $class
     * @param string             $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsAOf($value, $class, $message = '');

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     * @psalm-assert iterable<ExpectedType|class-string<ExpectedType>> $value
     *
     * @param iterable<object|string> $value
     * @param string                  $class
     * @param string                  $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsAOf($value, $class, $message = '');

    /**
     * @psalm-pure
     * @psalm-template UnexpectedType of object
     * @psalm-param class-string<UnexpectedType> $class
     *
     * @param null|object|string $value
     * @param string             $class
     * @param string             $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsNotA($value, $class, $message = '');

    /**
     * @psalm-pure
     * @psalm-template UnexpectedType of object
     * @psalm-param class-string<UnexpectedType> $class
     *
     * @param iterable<object|string> $value
     * @param string                  $class
     * @param string                  $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsNotA($value, $class, $message = '');

    /**
     * @psalm-pure
     * @psalm-param array<class-string> $classes
     *
     * @param null|object|string $value
     * @param string[]           $classes
     * @param string             $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsAnyOf($value, $classes, $message = '');

    /**
     * @psalm-pure
     * @psalm-param array<class-string> $classes
     *
     * @param iterable<object|string> $value
     * @param string[]                $classes
     * @param string                  $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsAnyOf($value, $classes, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert empty $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsEmpty($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<empty> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsEmpty($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNotEmpty($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNotEmpty($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<null> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNull($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNotNull($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|true $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrTrue($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<true> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allTrue($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|false $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrFalse($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<false> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allFalse($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNotFalse($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNotFalse($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIp($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIp($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIpv4($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIpv4($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIpv6($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIpv6($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrEmail($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allEmail($value, $message = '');

    /**
     * @param null|array $values
     * @param string     $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrUniqueValues($values, $message = '');

    /**
     * @param iterable<array> $values
     * @param string          $message
     *
     * @throws InvalidArgumentException
     */
    public static function allUniqueValues($values, $message = '');

    /**
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrEq($value, $expect, $message = '');

    /**
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allEq($value, $expect, $message = '');

    /**
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNotEq($value, $expect, $message = '');

    /**
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNotEq($value, $expect, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrSame($value, $expect, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allSame($value, $expect, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNotSame($value, $expect, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $expect
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNotSame($value, $expect, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrGreaterThan($value, $limit, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allGreaterThan($value, $limit, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrGreaterThanEq($value, $limit, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allGreaterThanEq($value, $limit, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrLessThan($value, $limit, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allLessThan($value, $limit, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrLessThanEq($value, $limit, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $limit
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allLessThanEq($value, $limit, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $min
     * @param mixed  $max
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrRange($value, $min, $max, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param mixed  $min
     * @param mixed  $max
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allRange($value, $min, $max, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param array  $values
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrOneOf($value, $values, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param array  $values
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allOneOf($value, $values, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param array  $values
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrInArray($value, $values, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param array  $values
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allInArray($value, $values, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $subString
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrContains($value, $subString, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $subString
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allContains($value, $subString, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $subString
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNotContains($value, $subString, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $subString
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNotContains($value, $subString, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNotWhitespaceOnly($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNotWhitespaceOnly($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $prefix
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrStartsWith($value, $prefix, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $prefix
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allStartsWith($value, $prefix, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $prefix
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNotStartsWith($value, $prefix, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $prefix
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNotStartsWith($value, $prefix, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrStartsWithLetter($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allStartsWithLetter($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $suffix
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrEndsWith($value, $suffix, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $suffix
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allEndsWith($value, $suffix, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $suffix
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNotEndsWith($value, $suffix, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $suffix
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNotEndsWith($value, $suffix, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $pattern
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrRegex($value, $pattern, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $pattern
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allRegex($value, $pattern, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $pattern
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrNotRegex($value, $pattern, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $pattern
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allNotRegex($value, $pattern, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrUnicodeLetters($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allUnicodeLetters($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrAlpha($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allAlpha($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrDigits($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allDigits($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrAlnum($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allAlnum($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|lowercase-string $value
     *
     * @param null|string $value
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrLower($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<lowercase-string> $value
     *
     * @param iterable<string> $value
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allLower($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrUpper($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allUpper($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param int         $length
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrLength($value, $length, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param int              $length
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allLength($value, $length, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param int|float   $min
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrMinLength($value, $min, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param int|float        $min
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allMinLength($value, $min, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param int|float   $max
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrMaxLength($value, $max, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param int|float        $max
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allMaxLength($value, $max, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param int|float   $min
     * @param int|float   $max
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrLengthBetween($value, $min, $max, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param int|float        $min
     * @param int|float        $max
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allLengthBetween($value, $min, $max, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrFileExists($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allFileExists($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrFile($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allFile($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrDirectory($value, $message = '');

    /**
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allDirectory($value, $message = '');

    /**
     * @param null|string $value
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrReadable($value, $message = '');

    /**
     * @param iterable<string> $value
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allReadable($value, $message = '');

    /**
     * @param null|string $value
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrWritable($value, $message = '');

    /**
     * @param iterable<string> $value
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allWritable($value, $message = '');

    /**
     * @psalm-assert null|class-string $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrClassExists($value, $message = '');

    /**
     * @psalm-assert iterable<class-string> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allClassExists($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     * @psalm-assert null|class-string<ExpectedType>|ExpectedType $value
     *
     * @param mixed         $value
     * @param string|object $class
     * @param string        $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrSubclassOf($value, $class, $message = '');

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $class
     * @psalm-assert iterable<class-string<ExpectedType>|ExpectedType> $value
     *
     * @param mixed         $value
     * @param string|object $class
     * @param string        $message
     *
     * @throws InvalidArgumentException
     */
    public static function allSubclassOf($value, $class, $message = '');

    /**
     * @psalm-assert null|class-string $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrInterfaceExists($value, $message = '');

    /**
     * @psalm-assert iterable<class-string> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allInterfaceExists($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $interface
     * @psalm-assert null|class-string<ExpectedType> $value
     *
     * @param mixed  $value
     * @param mixed  $interface
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrImplementsInterface($value, $interface, $message = '');

    /**
     * @psalm-pure
     * @psalm-template ExpectedType of object
     * @psalm-param class-string<ExpectedType> $interface
     * @psalm-assert iterable<class-string<ExpectedType>> $value
     *
     * @param mixed  $value
     * @param mixed  $interface
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allImplementsInterface($value, $interface, $message = '');

    /**
     * @psalm-pure
     * @psalm-param null|class-string|object $classOrObject
     *
     * @param null|string|object $classOrObject
     * @param mixed              $property
     * @param string             $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrPropertyExists($classOrObject, $property, $message = '');

    /**
     * @psalm-pure
     * @psalm-param iterable<class-string|object> $classOrObject
     *
     * @param iterable<string|object> $classOrObject
     * @param mixed                   $property
     * @param string                  $message
     *
     * @throws InvalidArgumentException
     */
    public static function allPropertyExists($classOrObject, $property, $message = '');

    /**
     * @psalm-pure
     * @psalm-param null|class-string|object $classOrObject
     *
     * @param null|string|object $classOrObject
     * @param mixed              $property
     * @param string             $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrPropertyNotExists($classOrObject, $property, $message = '');

    /**
     * @psalm-pure
     * @psalm-param iterable<class-string|object> $classOrObject
     *
     * @param iterable<string|object> $classOrObject
     * @param mixed                   $property
     * @param string                  $message
     *
     * @throws InvalidArgumentException
     */
    public static function allPropertyNotExists($classOrObject, $property, $message = '');

    /**
     * @psalm-pure
     * @psalm-param null|class-string|object $classOrObject
     *
     * @param null|string|object $classOrObject
     * @param mixed              $method
     * @param string             $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrMethodExists($classOrObject, $method, $message = '');

    /**
     * @psalm-pure
     * @psalm-param iterable<class-string|object> $classOrObject
     *
     * @param iterable<string|object> $classOrObject
     * @param mixed                   $method
     * @param string                  $message
     *
     * @throws InvalidArgumentException
     */
    public static function allMethodExists($classOrObject, $method, $message = '');

    /**
     * @psalm-pure
     * @psalm-param null|class-string|object $classOrObject
     *
     * @param null|string|object $classOrObject
     * @param mixed              $method
     * @param string             $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrMethodNotExists($classOrObject, $method, $message = '');

    /**
     * @psalm-pure
     * @psalm-param iterable<class-string|object> $classOrObject
     *
     * @param iterable<string|object> $classOrObject
     * @param mixed                   $method
     * @param string                  $message
     *
     * @throws InvalidArgumentException
     */
    public static function allMethodNotExists($classOrObject, $method, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|array $array
     * @param string|int $key
     * @param string     $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrKeyExists($array, $key, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<array> $array
     * @param string|int      $key
     * @param string          $message
     *
     * @throws InvalidArgumentException
     */
    public static function allKeyExists($array, $key, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|array $array
     * @param string|int $key
     * @param string     $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrKeyNotExists($array, $key, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<array> $array
     * @param string|int      $key
     * @param string          $message
     *
     * @throws InvalidArgumentException
     */
    public static function allKeyNotExists($array, $key, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|array-key $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrValidArrayKey($value, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<array-key> $value
     *
     * @param mixed  $value
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allValidArrayKey($value, $message = '');

    /**
     * @param null|Countable|array $array
     * @param int                  $number
     * @param string               $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrCount($array, $number, $message = '');

    /**
     * @param iterable<Countable|array> $array
     * @param int                       $number
     * @param string                    $message
     *
     * @throws InvalidArgumentException
     */
    public static function allCount($array, $number, $message = '');

    /**
     * @param null|Countable|array $array
     * @param int|float            $min
     * @param string               $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrMinCount($array, $min, $message = '');

    /**
     * @param iterable<Countable|array> $array
     * @param int|float                 $min
     * @param string                    $message
     *
     * @throws InvalidArgumentException
     */
    public static function allMinCount($array, $min, $message = '');

    /**
     * @param null|Countable|array $array
     * @param int|float            $max
     * @param string               $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrMaxCount($array, $max, $message = '');

    /**
     * @param iterable<Countable|array> $array
     * @param int|float                 $max
     * @param string                    $message
     *
     * @throws InvalidArgumentException
     */
    public static function allMaxCount($array, $max, $message = '');

    /**
     * @param null|Countable|array $array
     * @param int|float            $min
     * @param int|float            $max
     * @param string               $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrCountBetween($array, $min, $max, $message = '');

    /**
     * @param iterable<Countable|array> $array
     * @param int|float                 $min
     * @param int|float                 $max
     * @param string                    $message
     *
     * @throws InvalidArgumentException
     */
    public static function allCountBetween($array, $min, $max, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|list $array
     *
     * @param mixed  $array
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsList($array, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<list> $array
     *
     * @param mixed  $array
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsList($array, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert null|non-empty-list $array
     *
     * @param mixed  $array
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsNonEmptyList($array, $message = '');

    /**
     * @psalm-pure
     * @psalm-assert iterable<non-empty-list> $array
     *
     * @param mixed  $array
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsNonEmptyList($array, $message = '');

    /**
     * @psalm-pure
     * @psalm-template T
     * @psalm-param null|mixed|array<T> $array
     * @psalm-assert null|array<string, T> $array
     *
     * @param mixed  $array
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsMap($array, $message = '');

    /**
     * @psalm-pure
     * @psalm-template T
     * @psalm-param iterable<mixed|array<T>> $array
     * @psalm-assert iterable<array<string, T>> $array
     *
     * @param mixed  $array
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsMap($array, $message = '');

    /**
     * @psalm-pure
     * @psalm-template T
     * @psalm-param null|mixed|array<T> $array
     *
     * @param mixed  $array
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrIsNonEmptyMap($array, $message = '');

    /**
     * @psalm-pure
     * @psalm-template T
     * @psalm-param iterable<mixed|array<T>> $array
     *
     * @param mixed  $array
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public static function allIsNonEmptyMap($array, $message = '');

    /**
     * @psalm-pure
     *
     * @param null|string $value
     * @param string      $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrUuid($value, $message = '');

    /**
     * @psalm-pure
     *
     * @param iterable<string> $value
     * @param string           $message
     *
     * @throws InvalidArgumentException
     */
    public static function allUuid($value, $message = '');

    /**
     * @psalm-param class-string<Throwable> $class
     *
     * @param null|Closure $expression
     * @param string       $class
     * @param string       $message
     *
     * @throws InvalidArgumentException
     */
    public static function nullOrThrows($expression, $class = 'Exception', $message = '');

    /**
     * @psalm-param class-string<Throwable> $class
     *
     * @param iterable<Closure> $expression
     * @param string            $class
     * @param string            $message
     *
     * @throws InvalidArgumentException
     */
    public static function allThrows($expression, $class = 'Exception', $message = '');
}
