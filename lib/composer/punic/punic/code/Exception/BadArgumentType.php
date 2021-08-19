<?php

namespace Punic\Exception;

/**
 * An exception raised when a function meets an argument of an unsupported type.
 */
class BadArgumentType extends \Punic\Exception
{
    protected $argumentValue;

    protected $destinationTypeDescription;
    /**
     * Initializes the instance.
     *
     * @param mixed $argumentValue The value of the invalid argument
     * @param string $destinationTypeDescription The description of the destination type
     * @param \Exception $previous The previous exception used for the exception chaining
     */
    public function __construct($argumentValue, $destinationTypeDescription, $previous = null)
    {
        $this->argumentValue = $argumentValue;
        $this->destinationTypeDescription = $destinationTypeDescription;
        $type = gettype($argumentValue);
        switch ($type) {
            case 'boolean':
                $shownName = $argumentValue ? 'TRUE' : 'FALSE';
                break;
            case 'integer':
            case 'double':
                $shownName = strval($argumentValue);
                break;
            case 'string':
                $shownName = "'$argumentValue'";
                break;
            case 'object':
                $shownName = get_class($argumentValue);
                break;
            default:
                $shownName = $type;
                break;
        }
        $message = "Can't convert $shownName to a $destinationTypeDescription";
        parent::__construct($message, \Punic\Exception::BAD_ARGUMENT_TYPE, $previous);
    }

    /**
     * Retrieves the value of the invalid argument.
     *
     * @return mixed
     */
    public function getArgumentValue()
    {
        return $this->argumentValue;
    }

    /**
     * Retrieves the destination type (or a list of destination types).
     *
     * @return string|array<string>
     */
    public function getDestinationTypeDescription()
    {
        return $this->destinationTypeDescription;
    }
}
