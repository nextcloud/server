<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Constraints;

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
    public static $wording = array(
        'integer' => 'an integer',
        'number'  => 'a number',
        'boolean' => 'a boolean',
        'object'  => 'an object',
        'array'   => 'an array',
        'string'  => 'a string',
        'null'    => 'a null',
        'any'     => null, // validation of 'any' is always true so is not needed in message wording
        0         => null, // validation of a false-y value is always true, so not needed as well
    );

    /**
     * {@inheritdoc}
     */
    public function check(&$value = null, $schema = null, JsonPointer $path = null, $i = null)
    {
        $type = isset($schema->type) ? $schema->type : null;
        $isValid = false;
        $wording = array();

        if (is_array($type)) {
            $this->validateTypesArray($value, $type, $wording, $isValid, $path);
        } elseif (is_object($type)) {
            $this->checkUndefined($value, $type, $path);

            return;
        } else {
            $isValid = $this->validateType($value, $type);
        }

        if ($isValid === false) {
            if (!is_array($type)) {
                $this->validateTypeNameWording($type);
                $wording[] = self::$wording[$type];
            }
            $this->addError($path, ucwords(gettype($value)) . ' value found, but ' .
                $this->implodeWith($wording, ', ', 'or') . ' is required', 'type');
        }
    }

    /**
     * Validates the given $value against the array of types in $type. Sets the value
     * of $isValid to true, if at least one $type mateches the type of $value or the value
     * passed as $isValid is already true.
     *
     * @param mixed $value             Value to validate
     * @param array $type              TypeConstraints to check agains
     * @param array $validTypesWording An array of wordings of the valid types of the array $type
     * @param bool  $isValid           The current validation value
     * @param $path
     */
    protected function validateTypesArray(&$value, array $type, &$validTypesWording, &$isValid, $path)
    {
        foreach ($type as $tp) {
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
                    $isValid = $this->validateType($value, $tp);
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
        $implodedElements = array_merge(array($firsElements), $lastElement);

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
        if (!isset(self::$wording[$type])) {
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
    protected function validateType(&$value, $type)
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
            return $this->getTypeCheck()->isArray($value);
        }

        $coerce = $this->factory->getConfig(Constraint::CHECK_MODE_COERCE_TYPES);

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
            return is_string($value);
        }

        if ('email' === $type) {
            return is_string($value);
        }

        if ('null' === $type) {
            return is_null($value);
        }

        throw new InvalidArgumentException((is_object($value) ? 'object' : $value) . ' is an invalid type for ' . $type);
    }

    /**
     * Converts a value to boolean. For example, "true" becomes true.
     *
     * @param $value The value to convert to boolean
     *
     * @return bool|mixed
     */
    protected function toBoolean($value)
    {
        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        return $value;
    }

    /**
     * Converts a numeric string to a number. For example, "4" becomes 4.
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

        return $value;
    }

    protected function toInteger($value)
    {
        if (is_numeric($value) && (int) $value == $value) {
            return (int) $value; // cast to number
        }

        return $value;
    }
}
