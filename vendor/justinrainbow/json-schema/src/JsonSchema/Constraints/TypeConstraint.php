<?php

declare(strict_types=1);

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Constraints;

use JsonSchema\ConstraintError;
use JsonSchema\Entity\JsonPointer;
use JsonSchema\Exception\InvalidArgumentException;
use UnexpectedValueException as StandardUnexpectedValueException;

/**
 * The TypeConstraint Constraints, validates an element against a given type
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class TypeConstraint extends Constraint
{
    /**
     * @var array|string[] type wordings for validation error messages
     */
    public static $wording = [
        'integer' => 'an integer',
        'number'  => 'a number',
        'boolean' => 'a boolean',
        'object'  => 'an object',
        'array'   => 'an array',
        'string'  => 'a string',
        'null'    => 'a null',
        'any'     => null, // validation of 'any' is always true so is not needed in message wording
        0         => null, // validation of a false-y value is always true, so not needed as well
    ];

    /**
     * {@inheritdoc}
     */
    public function check(&$value = null, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        $type = isset($schema->type) ? $schema->type : null;
        $isValid = false;
        $coerce = $this->factory->getConfig(self::CHECK_MODE_COERCE_TYPES);
        $earlyCoerce = $this->factory->getConfig(self::CHECK_MODE_EARLY_COERCE);
        $wording = [];

        if (is_array($type)) {
            $this->validateTypesArray($value, $type, $wording, $isValid, $path, $coerce && $earlyCoerce);
            if (!$isValid && $coerce && !$earlyCoerce) {
                $this->validateTypesArray($value, $type, $wording, $isValid, $path, true);
            }
        } elseif (is_object($type)) {
            $this->checkUndefined($value, $type, $path);

            return;
        } else {
            $isValid = $this->validateType($value, $type, $coerce && $earlyCoerce);
            if (!$isValid && $coerce && !$earlyCoerce) {
                $isValid = $this->validateType($value, $type, true);
            }
        }

        if ($isValid === false) {
            if (!is_array($type)) {
                $this->validateTypeNameWording($type);
                $wording[] = self::$wording[$type];
            }
            $this->addError(ConstraintError::TYPE(), $path, [
                    'found' => gettype($value),
                    'expected' => $this->implodeWith($wording, ', ', 'or')
            ]);
        }
    }

    /**
     * Validates the given $value against the array of types in $type. Sets the value
     * of $isValid to true, if at least one $type mateches the type of $value or the value
     * passed as $isValid is already true.
     *
     * @param mixed        $value             Value to validate
     * @param array        $type              TypeConstraints to check against
     * @param array        $validTypesWording An array of wordings of the valid types of the array $type
     * @param bool         $isValid           The current validation value
     * @param ?JsonPointer $path
     * @param bool         $coerce
     */
    protected function validateTypesArray(&$value, array $type, &$validTypesWording, &$isValid, $path, $coerce = false)
    {
        foreach ($type as $tp) {
            // already valid, so no need to waste cycles looping over everything
            if ($isValid) {
                return;
            }

            // $tp can be an object, if it's a schema instead of a simple type, validate it
            // with a new type constraint
            if (is_object($tp)) {
                if (!$isValid) {
                    $validator = $this->factory->createInstanceFor('type');
                    $subSchema = new \stdClass();
                    $subSchema->type = $tp;
                    $validator->check($value, $subSchema, $path, null);
                    $error = $validator->getErrors();
                    $isValid = !(bool) $error;
                    $validTypesWording[] = self::$wording['object'];
                }
            } else {
                $this->validateTypeNameWording($tp);
                $validTypesWording[] = self::$wording[$tp];
                if (!$isValid) {
                    $isValid = $this->validateType($value, $tp, $coerce);
                }
            }
        }
    }

    /**
     * Implodes the given array like implode() with turned around parameters and with the
     * difference, that, if $listEnd isn't false, the last element delimiter is $listEnd instead of
     * $delimiter.
     *
     * @param array  $elements  The elements to implode
     * @param string $delimiter The delimiter to use
     * @param bool   $listEnd   The last delimiter to use (defaults to $delimiter)
     *
     * @return string
     */
    protected function implodeWith(array $elements, $delimiter = ', ', $listEnd = false)
    {
        if ($listEnd === false || !isset($elements[1])) {
            return implode($delimiter, $elements);
        }
        $lastElement  = array_slice($elements, -1);
        $firsElements = join($delimiter, array_slice($elements, 0, -1));
        $implodedElements = array_merge([$firsElements], $lastElement);

        return join(" $listEnd ", $implodedElements);
    }

    /**
     * Validates the given $type, if there's an associated self::$wording. If not, throws an
     * exception.
     *
     * @param string $type The type to validate
     *
     * @throws StandardUnexpectedValueException
     */
    protected function validateTypeNameWording($type)
    {
        if (!array_key_exists($type, self::$wording)) {
            throw new StandardUnexpectedValueException(
                sprintf(
                    'No wording for %s available, expected wordings are: [%s]',
                    var_export($type, true),
                    implode(', ', array_filter(self::$wording)))
            );
        }
    }

    /**
     * Verifies that a given value is of a certain type
     *
     * @param mixed  $value Value to validate
     * @param string $type  TypeConstraint to check against
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    protected function validateType(&$value, $type, $coerce = false)
    {
        //mostly the case for inline schema
        if (!$type) {
            return true;
        }

        if ('any' === $type) {
            return true;
        }

        if ('object' === $type) {
            return $this->getTypeCheck()->isObject($value);
        }

        if ('array' === $type) {
            if ($coerce) {
                $value = $this->toArray($value);
            }

            return $this->getTypeCheck()->isArray($value);
        }

        if ('integer' === $type) {
            if ($coerce) {
                $value = $this->toInteger($value);
            }

            return is_int($value);
        }

        if ('number' === $type) {
            if ($coerce) {
                $value = $this->toNumber($value);
            }

            return is_numeric($value) && !is_string($value);
        }

        if ('boolean' === $type) {
            if ($coerce) {
                $value = $this->toBoolean($value);
            }

            return is_bool($value);
        }

        if ('string' === $type) {
            if ($coerce) {
                $value = $this->toString($value);
            }

            return is_string($value);
        }

        if ('null' === $type) {
            if ($coerce) {
                $value = $this->toNull($value);
            }

            return is_null($value);
        }

        throw new InvalidArgumentException((is_object($value) ? 'object' : $value) . ' is an invalid type for ' . $type);
    }

    /**
     * Converts a value to boolean. For example, "true" becomes true.
     *
     * @param mixed $value The value to convert to boolean
     *
     * @return bool|mixed
     */
    protected function toBoolean($value)
    {
        if ($value === 1 || $value === 'true') {
            return true;
        }
        if (is_null($value) || $value === 0 || $value === 'false') {
            return false;
        }
        if ($this->getTypeCheck()->isArray($value) && count($value) === 1) {
            return $this->toBoolean(reset($value));
        }

        return $value;
    }

    /**
     * Converts a value to a number. For example, "4.5" becomes 4.5.
     *
     * @param mixed $value the value to convert to a number
     *
     * @return int|float|mixed
     */
    protected function toNumber($value)
    {
        if (is_numeric($value)) {
            return $value + 0; // cast to number
        }
        if (is_bool($value) || is_null($value)) {
            return (int) $value;
        }
        if ($this->getTypeCheck()->isArray($value) && count($value) === 1) {
            return $this->toNumber(reset($value));
        }

        return $value;
    }

    /**
     * Converts a value to an integer. For example, "4" becomes 4.
     *
     * @param mixed $value
     *
     * @return int|mixed
     */
    protected function toInteger($value)
    {
        $numberValue = $this->toNumber($value);
        if (is_numeric($numberValue) && (int) $numberValue == $numberValue) {
            return (int) $numberValue; // cast to number
        }

        return $value;
    }

    /**
     * Converts a value to an array containing that value. For example, [4] becomes 4.
     *
     * @param mixed $value
     *
     * @return array|mixed
     */
    protected function toArray($value)
    {
        if (is_scalar($value) || is_null($value)) {
            return [$value];
        }

        return $value;
    }

    /**
     * Convert a value to a string representation of that value. For example, null becomes "".
     *
     * @param mixed $value
     *
     * @return string|mixed
     */
    protected function toString($value)
    {
        if (is_numeric($value)) {
            return "$value";
        }
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        if (is_null($value)) {
            return '';
        }
        if ($this->getTypeCheck()->isArray($value) && count($value) === 1) {
            return $this->toString(reset($value));
        }

        return $value;
    }

    /**
     * Convert a value to a null. For example, 0 becomes null.
     *
     * @param mixed $value
     *
     * @return null|mixed
     */
    protected function toNull($value)
    {
        if ($value === 0 || $value === false || $value === '') {
            return null;
        }
        if ($this->getTypeCheck()->isArray($value) && count($value) === 1) {
            return $this->toNull(reset($value));
        }

        return $value;
    }
}
