<?php

/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal;

use MicrosoftAzure\Storage\Common\Exceptions\InvalidArgumentTypeException;

/**
 * Validates against a condition and throws an exception in case of failure.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class Validate
{
    /**
     * Throws exception if the provided variable type is not array.
     *
     * @param mixed  $var  The variable to check.
     * @param string $name The parameter name.
     *
     * @throws InvalidArgumentTypeException.
     *
     * @return void
     */
    public static function isArray($var, $name)
    {
        if (!is_array($var)) {
            throw new InvalidArgumentTypeException(gettype(array()), $name);
        }
    }

    /**
     * Throws exception if the provided variable can not convert to a string.
     *
     * @param mixed  $var  The variable to check.
     * @param string $name The parameter name.
     *
     * @throws InvalidArgumentTypeException
     *
     * @return void
     */
    public static function canCastAsString($var, $name)
    {
        try {
            (string)$var;
        } catch (\Exception $e) {
            throw new InvalidArgumentTypeException(gettype(''), $name);
        }
    }

    /**
     * Throws exception if the provided variable type is not boolean.
     *
     * @param mixed $var variable to check against.
     *
     * @throws InvalidArgumentTypeException
     *
     * @return void
     */
    public static function isBoolean($var)
    {
        (bool)$var;
    }

    /**
     * Throws exception if the provided variable is set to null.
     *
     * @param mixed  $var  The variable to check.
     * @param string $name The parameter name.
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public static function notNullOrEmpty($var, $name)
    {
        if (is_null($var) || (empty($var) && $var != '0')) {
            throw new \InvalidArgumentException(
                sprintf(Resources::NULL_OR_EMPTY_MSG, $name)
            );
        }
    }

    /**
     * Throws exception if the provided variable is not double.
     *
     * @param mixed  $var  The variable to check.
     * @param string $name The parameter name.
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public static function isDouble($var, $name)
    {
        if (!is_numeric($var)) {
            throw new InvalidArgumentTypeException('double', $name);
        }
    }

    /**
     * Throws exception if the provided variable type is not integer.
     *
     * @param mixed  $var  The variable to check.
     * @param string $name The parameter name.
     *
     * @throws InvalidArgumentTypeException
     *
     * @return void
     */
    public static function isInteger($var, $name)
    {
        try {
            (int)$var;
        } catch (\Exception $e) {
            throw new InvalidArgumentTypeException(gettype(123), $name);
        }
    }

    /**
     * Returns whether the variable is an empty or null string.
     *
     * @param string $var value.
     *
     * @return boolean
     */
    public static function isNullOrEmptyString($var)
    {
        try {
            (string)$var;
        } catch (\Exception $e) {
            return false;
        }

        return (!isset($var) || trim($var)==='');
    }

    /**
     * Throws exception if the provided condition is not satisfied.
     *
     * @param bool   $isSatisfied    condition result.
     * @param string $failureMessage the exception message
     *
     * @throws \Exception
     *
     * @return void
     */
    public static function isTrue($isSatisfied, $failureMessage)
    {
        if (!$isSatisfied) {
            throw new \InvalidArgumentException($failureMessage);
        }
    }

    /**
     * Throws exception if the provided $date doesn't implement \DateTimeInterface
     *
     * @param mixed $date variable to check against.
     *
     * @throws InvalidArgumentTypeException
     *
     * @return void
     */
    public static function isDate($date)
    {
        if (gettype($date) != 'object' || !($date instanceof \DateTimeInterface)) {
            throw new InvalidArgumentTypeException('DateTimeInterface');
        }
    }

    /**
     * Throws exception if the provided variable is set to null.
     *
     * @param mixed  $var  The variable to check.
     * @param string $name The parameter name.
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public static function notNull($var, $name)
    {
        if (is_null($var)) {
            throw new \InvalidArgumentException(sprintf(Resources::NULL_MSG, $name));
        }
    }

    /**
     * Throws exception if the object is not of the specified class type.
     *
     * @param mixed  $objectInstance An object that requires class type validation.
     * @param mixed  $classInstance  The instance of the class the the
     * object instance should be.
     * @param string $name           The name of the object.
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public static function isInstanceOf($objectInstance, $classInstance, $name)
    {
        Validate::notNull($classInstance, 'classInstance');
        if (is_null($objectInstance)) {
            return true;
        }

        $objectType = gettype($objectInstance);
        $classType  = gettype($classInstance);

        if ($objectType === $classType) {
            return true;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    Resources::INSTANCE_TYPE_VALIDATION_MSG,
                    $name,
                    $objectType,
                    $classType
                )
            );
        }
    }

    /**
     * Creates an anonymous function that checks if the given hostname is valid or not.
     *
     * @return callable
     */
    public static function getIsValidHostname()
    {
        return function ($hostname) {
            return Validate::isValidHostname($hostname);
        };
    }

    /**
     * Throws an exception if the string is not of a valid hostname.
     *
     * @param string $hostname String to check.
     *
     * @throws \InvalidArgumentException
     *
     * @return boolean
     */
    public static function isValidHostname($hostname)
    {
        if (defined('FILTER_VALIDATE_DOMAIN')) {
            $isValid = filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
        } else {
            // (less accurate) fallback for PHP < 7.0
            $isValid = preg_match('/^[a-z0-9_-]+(\.[a-z0-9_-]+)*$/i', $hostname);
        }

        if ($isValid) {
            return true;
        } else {
            throw new \RuntimeException(
                sprintf(Resources::INVALID_CONFIG_HOSTNAME, $hostname)
            );
        }
    }

    /**
     * Creates a anonymous function that check if the given uri is valid or not.
     *
     * @return callable
     */
    public static function getIsValidUri()
    {
        return function ($uri) {
            return Validate::isValidUri($uri);
        };
    }

    /**
     * Throws exception if the string is not of a valid uri.
     *
     * @param string $uri String to check.
     *
     * @throws \InvalidArgumentException
     *
     * @return boolean
     */
    public static function isValidUri($uri)
    {
        $isValid = filter_var($uri, FILTER_VALIDATE_URL);

        if ($isValid) {
            return true;
        } else {
            throw new \RuntimeException(
                sprintf(Resources::INVALID_CONFIG_URI, $uri)
            );
        }
    }

    /**
     * Throws exception if the provided variable type is not object.
     *
     * @param mixed  $var  The variable to check.
     * @param string $name The parameter name.
     *
     * @throws InvalidArgumentTypeException.
     *
     * @return boolean
     */
    public static function isObject($var, $name)
    {
        if (!is_object($var)) {
            throw new InvalidArgumentTypeException('object', $name);
        }

        return true;
    }

    /**
     * Throws exception if the object is not of the specified class type.
     *
     * @param mixed  $objectInstance An object that requires class type validation.
     * @param string $class          The class the object instance should be.
     * @param string $name           The parameter name.
     *
     * @throws \InvalidArgumentException
     *
     * @return boolean
     */
    public static function isA($objectInstance, $class, $name)
    {
        Validate::canCastAsString($class, 'class');
        Validate::notNull($objectInstance, 'objectInstance');
        Validate::isObject($objectInstance, 'objectInstance');

        $objectType = get_class($objectInstance);

        if (is_a($objectInstance, $class)) {
            return true;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    Resources::INSTANCE_TYPE_VALIDATION_MSG,
                    $name,
                    $objectType,
                    $class
                )
            );
        }
    }

    /**
     * Validate if method exists in object
     *
     * @param object $objectInstance An object that requires method existing
     *                               validation
     * @param string $method         Method name
     * @param string $name           The parameter name
     *
     * @return boolean
     */
    public static function methodExists($objectInstance, $method, $name)
    {
        Validate::canCastAsString($method, 'method');
        Validate::notNull($objectInstance, 'objectInstance');
        Validate::isObject($objectInstance, 'objectInstance');

        if (method_exists($objectInstance, $method)) {
            return true;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    Resources::ERROR_METHOD_NOT_FOUND,
                    $method,
                    $name
                )
            );
        }
    }

    /**
     * Validate if string is date formatted
     *
     * @param string $value Value to validate
     * @param string $name  Name of parameter to insert in erro message
     *
     * @throws \InvalidArgumentException
     *
     * @return boolean
     */
    public static function isDateString($value, $name)
    {
        Validate::canCastAsString($value, 'value');

        try {
            new \DateTime($value);
            return true;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                sprintf(
                    Resources::ERROR_INVALID_DATE_STRING,
                    $name,
                    $value
                )
            );
        }
    }

    /**
     * Validate if the provided array has key, throw exception otherwise.
     *
     * @param  string  $key   The key to be searched.
     * @param  string  $name  The name of the array.
     * @param  array   $array The array to be validated.
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     *
     * @return  boolean
     */
    public static function hasKey($key, $name, array $array)
    {
        Validate::isArray($array, $name);

        if (!array_key_exists($key, $array)) {
            throw new \UnexpectedValueException(
                sprintf(
                    Resources::INVALID_VALUE_MSG,
                    $name,
                    sprintf(Resources::ERROR_KEY_NOT_EXIST, $key)
                )
            );
        }

        return true;
    }
}
