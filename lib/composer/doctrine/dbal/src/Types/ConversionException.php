<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Exception;
use Throwable;

use function get_class;
use function gettype;
use function implode;
use function is_object;
use function is_scalar;
use function sprintf;
use function strlen;
use function substr;
use function var_export;

/**
 * Conversion Exception is thrown when the database to PHP conversion fails.
 *
 * @psalm-immutable
 */
class ConversionException extends Exception
{
    /**
     * Thrown when a Database to Doctrine Type Conversion fails.
     *
     * @param string $value
     * @param string $toType
     *
     * @return ConversionException
     */
    public static function conversionFailed($value, $toType, ?Throwable $previous = null)
    {
        $value = strlen($value) > 32 ? substr($value, 0, 20) . '...' : $value;

        return new self('Could not convert database value "' . $value . '" to Doctrine Type ' . $toType, 0, $previous);
    }

    /**
     * Thrown when a Database to Doctrine Type Conversion fails and we can make a statement
     * about the expected format.
     *
     * @param string $value
     * @param string $toType
     * @param string $expectedFormat
     *
     * @return ConversionException
     */
    public static function conversionFailedFormat($value, $toType, $expectedFormat, ?Throwable $previous = null)
    {
        $value = strlen($value) > 32 ? substr($value, 0, 20) . '...' : $value;

        return new self(
            'Could not convert database value "' . $value . '" to Doctrine Type ' .
            $toType . '. Expected format: ' . $expectedFormat,
            0,
            $previous
        );
    }

    /**
     * Thrown when the PHP value passed to the converter was not of the expected type.
     *
     * @param mixed    $value
     * @param string   $toType
     * @param string[] $possibleTypes
     *
     * @return ConversionException
     */
    public static function conversionFailedInvalidType(
        $value,
        $toType,
        array $possibleTypes,
        ?Throwable $previous = null
    ) {
        if (is_scalar($value) || $value === null) {
            return new self(sprintf(
                'Could not convert PHP value %s to type %s. Expected one of the following types: %s',
                var_export($value, true),
                $toType,
                implode(', ', $possibleTypes)
            ), 0, $previous);
        }

        return new self(sprintf(
            'Could not convert PHP value of type %s to type %s. Expected one of the following types: %s',
            is_object($value) ? get_class($value) : gettype($value),
            $toType,
            implode(', ', $possibleTypes)
        ), 0, $previous);
    }

    /**
     * @param mixed  $value
     * @param string $format
     * @param string $error
     *
     * @return ConversionException
     */
    public static function conversionFailedSerialization($value, $format, $error)
    {
        $actualType = is_object($value) ? get_class($value) : gettype($value);

        return new self(sprintf(
            "Could not convert PHP type '%s' to '%s', as an '%s' error was triggered by the serialization",
            $actualType,
            $format,
            $error
        ));
    }

    public static function conversionFailedUnserialization(string $format, string $error): self
    {
        return new self(sprintf(
            "Could not convert database value to '%s' as an error was triggered by the unserialization: '%s'",
            $format,
            $error
        ));
    }
}
