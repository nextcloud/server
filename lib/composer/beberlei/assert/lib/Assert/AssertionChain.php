<?php

/**
 * Assert
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Assert;

use LogicException;
use ReflectionClass;

/**
 * Chaining builder for assertions.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 *
 * @method AssertionChain alnum(string|callable $message = null, string $propertyPath = null) Assert that value is alphanumeric.
 * @method AssertionChain base64(string|callable $message = null, string $propertyPath = null) Assert that a constant is defined.
 * @method AssertionChain between(mixed $lowerLimit, mixed $upperLimit, string|callable $message = null, string $propertyPath = null) Assert that a value is greater or equal than a lower limit, and less than or equal to an upper limit.
 * @method AssertionChain betweenExclusive(mixed $lowerLimit, mixed $upperLimit, string|callable $message = null, string $propertyPath = null) Assert that a value is greater than a lower limit, and less than an upper limit.
 * @method AssertionChain betweenLength(int $minLength, int $maxLength, string|callable $message = null, string $propertyPath = null, string $encoding = 'utf8') Assert that string length is between min and max lengths.
 * @method AssertionChain boolean(string|callable $message = null, string $propertyPath = null) Assert that value is php boolean.
 * @method AssertionChain choice(array $choices, string|callable $message = null, string $propertyPath = null) Assert that value is in array of choices.
 * @method AssertionChain choicesNotEmpty(array $choices, string|callable $message = null, string $propertyPath = null) Determines if the values array has every choice as key and that this choice has content.
 * @method AssertionChain classExists(string|callable $message = null, string $propertyPath = null) Assert that the class exists.
 * @method AssertionChain contains(string $needle, string|callable $message = null, string $propertyPath = null, string $encoding = 'utf8') Assert that string contains a sequence of chars.
 * @method AssertionChain count(int $count, string|callable $message = null, string $propertyPath = null) Assert that the count of countable is equal to count.
 * @method AssertionChain date(string $format, string|callable $message = null, string $propertyPath = null) Assert that date is valid and corresponds to the given format.
 * @method AssertionChain defined(string|callable $message = null, string $propertyPath = null) Assert that a constant is defined.
 * @method AssertionChain digit(string|callable $message = null, string $propertyPath = null) Validates if an integer or integerish is a digit.
 * @method AssertionChain directory(string|callable $message = null, string $propertyPath = null) Assert that a directory exists.
 * @method AssertionChain e164(string|callable $message = null, string $propertyPath = null) Assert that the given string is a valid E164 Phone Number.
 * @method AssertionChain email(string|callable $message = null, string $propertyPath = null) Assert that value is an email address (using input_filter/FILTER_VALIDATE_EMAIL).
 * @method AssertionChain endsWith(string $needle, string|callable $message = null, string $propertyPath = null, string $encoding = 'utf8') Assert that string ends with a sequence of chars.
 * @method AssertionChain eq(mixed $value2, string|callable $message = null, string $propertyPath = null) Assert that two values are equal (using ==).
 * @method AssertionChain eqArraySubset(mixed $value2, string|callable $message = null, string $propertyPath = null) Assert that the array contains the subset.
 * @method AssertionChain extensionLoaded(string|callable $message = null, string $propertyPath = null) Assert that extension is loaded.
 * @method AssertionChain extensionVersion(string $operator, mixed $version, string|callable $message = null, string $propertyPath = null) Assert that extension is loaded and a specific version is installed.
 * @method AssertionChain false(string|callable $message = null, string $propertyPath = null) Assert that the value is boolean False.
 * @method AssertionChain file(string|callable $message = null, string $propertyPath = null) Assert that a file exists.
 * @method AssertionChain float(string|callable $message = null, string $propertyPath = null) Assert that value is a php float.
 * @method AssertionChain greaterOrEqualThan(mixed $limit, string|callable $message = null, string $propertyPath = null) Determines if the value is greater or equal than given limit.
 * @method AssertionChain greaterThan(mixed $limit, string|callable $message = null, string $propertyPath = null) Determines if the value is greater than given limit.
 * @method AssertionChain implementsInterface(string $interfaceName, string|callable $message = null, string $propertyPath = null) Assert that the class implements the interface.
 * @method AssertionChain inArray(array $choices, string|callable $message = null, string $propertyPath = null) Assert that value is in array of choices. This is an alias of Assertion::choice().
 * @method AssertionChain integer(string|callable $message = null, string $propertyPath = null) Assert that value is a php integer.
 * @method AssertionChain integerish(string|callable $message = null, string $propertyPath = null) Assert that value is a php integer'ish.
 * @method AssertionChain interfaceExists(string|callable $message = null, string $propertyPath = null) Assert that the interface exists.
 * @method AssertionChain ip(int $flag = null, string|callable $message = null, string $propertyPath = null) Assert that value is an IPv4 or IPv6 address.
 * @method AssertionChain ipv4(int $flag = null, string|callable $message = null, string $propertyPath = null) Assert that value is an IPv4 address.
 * @method AssertionChain ipv6(int $flag = null, string|callable $message = null, string $propertyPath = null) Assert that value is an IPv6 address.
 * @method AssertionChain isArray(string|callable $message = null, string $propertyPath = null) Assert that value is an array.
 * @method AssertionChain isArrayAccessible(string|callable $message = null, string $propertyPath = null) Assert that value is an array or an array-accessible object.
 * @method AssertionChain isCallable(string|callable $message = null, string $propertyPath = null) Determines that the provided value is callable.
 * @method AssertionChain isCountable(string|callable $message = null, string $propertyPath = null) Assert that value is countable.
 * @method AssertionChain isInstanceOf(string $className, string|callable $message = null, string $propertyPath = null) Assert that value is instance of given class-name.
 * @method AssertionChain isJsonString(string|callable $message = null, string $propertyPath = null) Assert that the given string is a valid json string.
 * @method AssertionChain isObject(string|callable $message = null, string $propertyPath = null) Determines that the provided value is an object.
 * @method AssertionChain isResource(string|callable $message = null, string $propertyPath = null) Assert that value is a resource.
 * @method AssertionChain isTraversable(string|callable $message = null, string $propertyPath = null) Assert that value is an array or a traversable object.
 * @method AssertionChain keyExists(string|int $key, string|callable $message = null, string $propertyPath = null) Assert that key exists in an array.
 * @method AssertionChain keyIsset(string|int $key, string|callable $message = null, string $propertyPath = null) Assert that key exists in an array/array-accessible object using isset().
 * @method AssertionChain keyNotExists(string|int $key, string|callable $message = null, string $propertyPath = null) Assert that key does not exist in an array.
 * @method AssertionChain length(int $length, string|callable $message = null, string $propertyPath = null, string $encoding = 'utf8') Assert that string has a given length.
 * @method AssertionChain lessOrEqualThan(mixed $limit, string|callable $message = null, string $propertyPath = null) Determines if the value is less or equal than given limit.
 * @method AssertionChain lessThan(mixed $limit, string|callable $message = null, string $propertyPath = null) Determines if the value is less than given limit.
 * @method AssertionChain max(mixed $maxValue, string|callable $message = null, string $propertyPath = null) Assert that a number is smaller as a given limit.
 * @method AssertionChain maxCount(int $count, string|callable $message = null, string $propertyPath = null) Assert that the countable have at most $count elements.
 * @method AssertionChain maxLength(int $maxLength, string|callable $message = null, string $propertyPath = null, string $encoding = 'utf8') Assert that string value is not longer than $maxLength chars.
 * @method AssertionChain methodExists(mixed $object, string|callable $message = null, string $propertyPath = null) Determines that the named method is defined in the provided object.
 * @method AssertionChain min(mixed $minValue, string|callable $message = null, string $propertyPath = null) Assert that a value is at least as big as a given limit.
 * @method AssertionChain minCount(int $count, string|callable $message = null, string $propertyPath = null) Assert that the countable have at least $count elements.
 * @method AssertionChain minLength(int $minLength, string|callable $message = null, string $propertyPath = null, string $encoding = 'utf8') Assert that a string is at least $minLength chars long.
 * @method AssertionChain noContent(string|callable $message = null, string $propertyPath = null) Assert that value is empty.
 * @method AssertionChain notBlank(string|callable $message = null, string $propertyPath = null) Assert that value is not blank.
 * @method AssertionChain notContains(string $needle, string|callable $message = null, string $propertyPath = null, string $encoding = 'utf8') Assert that string does not contains a sequence of chars.
 * @method AssertionChain notEmpty(string|callable $message = null, string $propertyPath = null) Assert that value is not empty.
 * @method AssertionChain notEmptyKey(string|int $key, string|callable $message = null, string $propertyPath = null) Assert that key exists in an array/array-accessible object and its value is not empty.
 * @method AssertionChain notEq(mixed $value2, string|callable $message = null, string $propertyPath = null) Assert that two values are not equal (using ==).
 * @method AssertionChain notInArray(array $choices, string|callable $message = null, string $propertyPath = null) Assert that value is not in array of choices.
 * @method AssertionChain notIsInstanceOf(string $className, string|callable $message = null, string $propertyPath = null) Assert that value is not instance of given class-name.
 * @method AssertionChain notNull(string|callable $message = null, string $propertyPath = null) Assert that value is not null.
 * @method AssertionChain notRegex(string $pattern, string|callable $message = null, string $propertyPath = null) Assert that value does not match a regex.
 * @method AssertionChain notSame(mixed $value2, string|callable $message = null, string $propertyPath = null) Assert that two values are not the same (using ===).
 * @method AssertionChain null(string|callable $message = null, string $propertyPath = null) Assert that value is null.
 * @method AssertionChain numeric(string|callable $message = null, string $propertyPath = null) Assert that value is numeric.
 * @method AssertionChain objectOrClass(string|callable $message = null, string $propertyPath = null) Assert that the value is an object, or a class that exists.
 * @method AssertionChain phpVersion(mixed $version, string|callable $message = null, string $propertyPath = null) Assert on PHP version.
 * @method AssertionChain propertiesExist(array $properties, string|callable $message = null, string $propertyPath = null) Assert that the value is an object or class, and that the properties all exist.
 * @method AssertionChain propertyExists(string $property, string|callable $message = null, string $propertyPath = null) Assert that the value is an object or class, and that the property exists.
 * @method AssertionChain range(mixed $minValue, mixed $maxValue, string|callable $message = null, string $propertyPath = null) Assert that value is in range of numbers.
 * @method AssertionChain readable(string|callable $message = null, string $propertyPath = null) Assert that the value is something readable.
 * @method AssertionChain regex(string $pattern, string|callable $message = null, string $propertyPath = null) Assert that value matches a regex.
 * @method AssertionChain same(mixed $value2, string|callable $message = null, string $propertyPath = null) Assert that two values are the same (using ===).
 * @method AssertionChain satisfy(callable $callback, string|callable $message = null, string $propertyPath = null) Assert that the provided value is valid according to a callback.
 * @method AssertionChain scalar(string|callable $message = null, string $propertyPath = null) Assert that value is a PHP scalar.
 * @method AssertionChain startsWith(string $needle, string|callable $message = null, string $propertyPath = null, string $encoding = 'utf8') Assert that string starts with a sequence of chars.
 * @method AssertionChain string(string|callable $message = null, string $propertyPath = null) Assert that value is a string.
 * @method AssertionChain subclassOf(string $className, string|callable $message = null, string $propertyPath = null) Assert that value is subclass of given class-name.
 * @method AssertionChain true(string|callable $message = null, string $propertyPath = null) Assert that the value is boolean True.
 * @method AssertionChain uniqueValues(string|callable $message = null, string $propertyPath = null) Assert that values in array are unique (using strict equality).
 * @method AssertionChain url(string|callable $message = null, string $propertyPath = null) Assert that value is an URL.
 * @method AssertionChain uuid(string|callable $message = null, string $propertyPath = null) Assert that the given string is a valid UUID.
 * @method AssertionChain version(string $operator, string $version2, string|callable $message = null, string $propertyPath = null) Assert comparison of two versions.
 * @method AssertionChain writeable(string|callable $message = null, string $propertyPath = null) Assert that the value is something writeable.
 */
class AssertionChain
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string|callable|null
     */
    private $defaultMessage;

    /**
     * @var string|null
     */
    private $defaultPropertyPath;

    /**
     * Return each assertion as always valid.
     *
     * @var bool
     */
    private $alwaysValid = false;

    /**
     * Perform assertion on every element of array or traversable.
     *
     * @var bool
     */
    private $all = false;

    /** @var string|Assertion Class to use for assertion calls */
    private $assertionClassName = 'Assert\Assertion';

    /**
     * AssertionChain constructor.
     *
     * @param mixed $value
     * @param string|callable|null $defaultMessage
     */
    public function __construct($value, $defaultMessage = null, string $defaultPropertyPath = null)
    {
        $this->value = $value;
        $this->defaultMessage = $defaultMessage;
        $this->defaultPropertyPath = $defaultPropertyPath;
    }

    /**
     * Call assertion on the current value in the chain.
     *
     * @param string $methodName
     * @param array $args
     */
    public function __call($methodName, $args): AssertionChain
    {
        if (true === $this->alwaysValid) {
            return $this;
        }

        if (!\method_exists($this->assertionClassName, $methodName)) {
            throw new \RuntimeException("Assertion '".$methodName."' does not exist.");
        }

        $reflClass = new ReflectionClass($this->assertionClassName);
        $method = $reflClass->getMethod($methodName);

        \array_unshift($args, $this->value);
        $params = $method->getParameters();

        foreach ($params as $idx => $param) {
            if (isset($args[$idx])) {
                continue;
            }

            if ('message' == $param->getName()) {
                $args[$idx] = $this->defaultMessage;
            }

            if ('propertyPath' == $param->getName()) {
                $args[$idx] = $this->defaultPropertyPath;
            }
        }

        if ($this->all) {
            $methodName = 'all'.$methodName;
        }

        \call_user_func_array([$this->assertionClassName, $methodName], $args);

        return $this;
    }

    /**
     * Switch chain into validation mode for an array of values.
     */
    public function all(): AssertionChain
    {
        $this->all = true;

        return $this;
    }

    /**
     * Switch chain into mode allowing nulls, ignoring further assertions.
     */
    public function nullOr(): AssertionChain
    {
        if (null === $this->value) {
            $this->alwaysValid = true;
        }

        return $this;
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function setAssertionClassName($className): AssertionChain
    {
        if (!\is_string($className)) {
            throw new LogicException('Exception class name must be passed as a string');
        }

        if (Assertion::class !== $className && !\is_subclass_of($className, Assertion::class)) {
            throw new LogicException($className.' is not (a subclass of) '.Assertion::class);
        }

        $this->assertionClassName = $className;

        return $this;
    }
}
