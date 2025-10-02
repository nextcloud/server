<?php

namespace Punic\Exception;

/**
 * An exception raised when an invalid data override is provided.
 */
class InvalidOverride extends \Punic\Exception
{
    /**
     * Initializes the instance.
     *
     * @param mixed $data The data being overridden
     * @param mixed $override The override data
     * @param \Exception|null $previous The previous exception used for the exception chaining
     */
    public function __construct($data, $override, $previous = null)
    {
        $message = 'Cannot override ' . $this->dataToString($data) . ' with ' . $this->dataToString($override);
        parent::__construct($message, \Punic\Exception::INVALID_OVERRIDE, $previous);
    }

    /**
     * Convert override data to a string.
     *
     * @return string
     */
    protected function dataToString($data)
    {
        if (is_array($data)) {
            return 'array with keys ' . implode(', ', array_keys($data));
        }

        return gettype($data) . ' value ' . $data;
    }
}
