<?php

namespace Punic\Exception;

/**
 * An exception raised when an data file has been hit.
 */
class InvalidDataFile extends \Punic\Exception
{
    protected $identifier;

    /**
     * Initializes the instance.
     *
     * @param mixed $identifier The bad data file identifier
     * @param \Exception $previous The previous exception used for the exception chaining
     */
    public function __construct($identifier, $previous = null)
    {
        $this->identifier = $identifier;
        $type = gettype($identifier);
        if ($type === 'string') {
            $message = "'$identifier' is not a valid data file identifier";
        } else {
            $message = "A valid identifier should be a string, $type received";
        }
        parent::__construct($message, \Punic\Exception::INVALID_DATAFILE, $previous);
    }

    /**
     * Retrieves the bad data file identifier.
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
