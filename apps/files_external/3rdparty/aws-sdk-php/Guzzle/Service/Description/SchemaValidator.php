<?php

namespace Guzzle\Service\Description;

use Guzzle\Common\ToArrayInterface;

/**
 * Default parameter validator
 */
class SchemaValidator implements ValidatorInterface
{
    /** @var self Cache instance of the object */
    protected static $instance;

    /** @var bool Whether or not integers are converted to strings when an integer is received for a string input */
    protected $castIntegerToStringType;

    /** @var array Errors encountered while validating */
    protected $errors;

    /**
     * @return self
     * @codeCoverageIgnore
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param bool $castIntegerToStringType Set to true to convert integers into strings when a required type is a
     *                                      string and the input value is an integer. Defaults to true.
     */
    public function __construct($castIntegerToStringType = true)
    {
        $this->castIntegerToStringType = $castIntegerToStringType;
    }

    public function validate(Parameter $param, &$value)
    {
        $this->errors = array();
        $this->recursiveProcess($param, $value);

        if (empty($this->errors)) {
            return true;
        } else {
            sort($this->errors);
            return false;
        }
    }

    /**
     * Get the errors encountered while validating
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors ?: array();
    }

    /**
     * Recursively validate a parameter
     *
     * @param Parameter $param  API parameter being validated
     * @param mixed     $value  Value to validate and validate. The value may change during this validate.
     * @param string    $path   Current validation path (used for error reporting)
     * @param int       $depth  Current depth in the validation validate
     *
     * @return bool Returns true if valid, or false if invalid
     */
    protected function recursiveProcess(Parameter $param, &$value, $path = '', $depth = 0)
    {
        // Update the value by adding default or static values
        $value = $param->getValue($value);

        $required = $param->getRequired();
        // if the value is null and the parameter is not required or is static, then skip any further recursion
        if ((null === $value && !$required) || $param->getStatic()) {
            return true;
        }

        $type = $param->getType();
        // Attempt to limit the number of times is_array is called by tracking if the value is an array
        $valueIsArray = is_array($value);
        // If a name is set then update the path so that validation messages are more helpful
        if ($name = $param->getName()) {
            $path .= "[{$name}]";
        }

        if ($type == 'object') {

            // Objects are either associative arrays, ToArrayInterface, or some other object
            if ($param->getInstanceOf()) {
                $instance = $param->getInstanceOf();
                if (!($value instanceof $instance)) {
                    $this->errors[] = "{$path} must be an instance of {$instance}";
                    return false;
                }
            }

            // Determine whether or not this "value" has properties and should be traversed
            $traverse = $temporaryValue = false;

            // Convert the value to an array
            if (!$valueIsArray && $value instanceof ToArrayInterface) {
                $value = $value->toArray();
            }

            if ($valueIsArray) {
                // Ensure that the array is associative and not numerically indexed
                if (isset($value[0])) {
                    $this->errors[] = "{$path} must be an array of properties. Got a numerically indexed array.";
                    return false;
                }
                $traverse = true;
            } elseif ($value === null) {
                // Attempt to let the contents be built up by default values if possible
                $value = array();
                $temporaryValue = $valueIsArray = $traverse = true;
            }

            if ($traverse) {

                if ($properties = $param->getProperties()) {
                    // if properties were found, the validate each property of the value
                    foreach ($properties as $property) {
                        $name = $property->getName();
                        if (isset($value[$name])) {
                            $this->recursiveProcess($property, $value[$name], $path, $depth + 1);
                        } else {
                            $current = null;
                            $this->recursiveProcess($property, $current, $path, $depth + 1);
                            // Only set the value if it was populated with something
                            if ($current) {
                                $value[$name] = $current;
                            }
                        }
                    }
                }

                $additional = $param->getAdditionalProperties();
                if ($additional !== true) {
                    // If additional properties were found, then validate each against the additionalProperties attr.
                    $keys = array_keys($value);
                    // Determine the keys that were specified that were not listed in the properties of the schema
                    $diff = array_diff($keys, array_keys($properties));
                    if (!empty($diff)) {
                        // Determine which keys are not in the properties
                        if ($additional instanceOf Parameter) {
                            foreach ($diff as $key) {
                                $this->recursiveProcess($additional, $value[$key], "{$path}[{$key}]", $depth);
                            }
                        } else {
                            // if additionalProperties is set to false and there are additionalProperties in the values, then fail
                            $keys = array_keys($value);
                            $this->errors[] = sprintf('%s[%s] is not an allowed property', $path, reset($keys));
                        }
                    }
                }

                // A temporary value will be used to traverse elements that have no corresponding input value.
                // This allows nested required parameters with default values to bubble up into the input.
                // Here we check if we used a temp value and nothing bubbled up, then we need to remote the value.
                if ($temporaryValue && empty($value)) {
                    $value = null;
                    $valueIsArray = false;
                }
            }

        } elseif ($type == 'array' && $valueIsArray && $param->getItems()) {
            foreach ($value as $i => &$item) {
                // Validate each item in an array against the items attribute of the schema
                $this->recursiveProcess($param->getItems(), $item, $path . "[{$i}]", $depth + 1);
            }
        }

        // If the value is required and the type is not null, then there is an error if the value is not set
        if ($required && $value === null && $type != 'null') {
            $message = "{$path} is " . ($param->getType() ? ('a required ' . implode(' or ', (array) $param->getType())) : 'required');
            if ($param->getDescription()) {
                $message .= ': ' . $param->getDescription();
            }
            $this->errors[] = $message;
            return false;
        }

        // Validate that the type is correct. If the type is string but an integer was passed, the class can be
        // instructed to cast the integer to a string to pass validation. This is the default behavior.
        if ($type && (!$type = $this->determineType($type, $value))) {
            if ($this->castIntegerToStringType && $param->getType() == 'string' && is_integer($value)) {
                $value = (string) $value;
            } else {
                $this->errors[] = "{$path} must be of type " . implode(' or ', (array) $param->getType());
            }
        }

        // Perform type specific validation for strings, arrays, and integers
        if ($type == 'string') {

            // Strings can have enums which are a list of predefined values
            if (($enum = $param->getEnum()) && !in_array($value, $enum)) {
                $this->errors[] = "{$path} must be one of " . implode(' or ', array_map(function ($s) {
                    return '"' . addslashes($s) . '"';
                }, $enum));
            }
            // Strings can have a regex pattern that the value must match
            if (($pattern  = $param->getPattern()) && !preg_match($pattern, $value)) {
                $this->errors[] = "{$path} must match the following regular expression: {$pattern}";
            }

            $strLen = null;
            if ($min = $param->getMinLength()) {
                $strLen = strlen($value);
                if ($strLen < $min) {
                    $this->errors[] = "{$path} length must be greater than or equal to {$min}";
                }
            }
            if ($max = $param->getMaxLength()) {
                if (($strLen ?: strlen($value)) > $max) {
                    $this->errors[] = "{$path} length must be less than or equal to {$max}";
                }
            }

        } elseif ($type == 'array') {

            $size = null;
            if ($min = $param->getMinItems()) {
                $size = count($value);
                if ($size < $min) {
                    $this->errors[] = "{$path} must contain {$min} or more elements";
                }
            }
            if ($max = $param->getMaxItems()) {
                if (($size ?: count($value)) > $max) {
                    $this->errors[] = "{$path} must contain {$max} or fewer elements";
                }
            }

        } elseif ($type == 'integer' || $type == 'number' || $type == 'numeric') {
            if (($min = $param->getMinimum()) && $value < $min) {
                $this->errors[] = "{$path} must be greater than or equal to {$min}";
            }
            if (($max = $param->getMaximum()) && $value > $max) {
                $this->errors[] = "{$path} must be less than or equal to {$max}";
            }
        }

        return empty($this->errors);
    }

    /**
     * From the allowable types, determine the type that the variable matches
     *
     * @param string $type  Parameter type
     * @param mixed  $value Value to determine the type
     *
     * @return string|bool Returns the matching type on
     */
    protected function determineType($type, $value)
    {
        foreach ((array) $type as $t) {
            if ($t == 'string' && (is_string($value) || (is_object($value) && method_exists($value, '__toString')))) {
                return 'string';
            } elseif ($t == 'object' && (is_array($value) || is_object($value))) {
                return 'object';
            } elseif ($t == 'array' && is_array($value)) {
                return 'array';
            } elseif ($t == 'integer' && is_integer($value)) {
                return 'integer';
            } elseif ($t == 'boolean' && is_bool($value)) {
                return 'boolean';
            } elseif ($t == 'number' && is_numeric($value)) {
                return 'number';
            } elseif ($t == 'numeric' && is_numeric($value)) {
                return 'numeric';
            } elseif ($t == 'null' && !$value) {
                return 'null';
            } elseif ($t == 'any') {
                return 'any';
            }
        }

        return false;
    }
}
