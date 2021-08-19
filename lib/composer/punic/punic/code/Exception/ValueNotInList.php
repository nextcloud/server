<?php

namespace Punic\Exception;

/**
 * An exception raised when a function meets an argument of an unsupported type.
 */
class ValueNotInList extends \Punic\Exception
{
    protected $value;

    protected $allowedValues;
    /**
     * Initializes the instance.
     *
     * @param string|numeric $value The invalid value
     * @param array<string|numeric> $allowedValues The list of valid values
     * @param \Exception $previous The previous exception used for the exception chaining
     */
    public function __construct($value, $allowedValues, $previous = null)
    {
        $this->value = $value;
        $this->allowedValues = $allowedValues;
        $message = "'$value' is not valid. Acceptable values are: '".implode("', '", $allowedValues)."'";
        parent::__construct($message, \Punic\Exception::VALUE_NOT_IN_LIST, $previous);
    }

    /**
     * Retrieves the invalid value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Retrieves the list of valid values.
     *
     * @return array<string|numeric>
     */
    public function getAllowedValues()
    {
        return $this->allowedValues;
    }
}
