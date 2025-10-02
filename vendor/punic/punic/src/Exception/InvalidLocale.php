<?php

namespace Punic\Exception;

/**
 * An exception raised when an invalid locale specification has been hit.
 */
class InvalidLocale extends \Punic\Exception
{
    protected $locale;

    /**
     * Initializes the instance.
     *
     * @param mixed $locale The bad locale
     * @param \Exception|null $previous The previous exception used for the exception chaining
     */
    public function __construct($locale, $previous = null)
    {
        $this->locale = $locale;
        $type = gettype($locale);
        if ($type === 'string') {
            $message = "'{$locale}' is not a valid locale identifier";
        } else {
            $message = "A valid locale should be a string, {$type} received";
        }
        parent::__construct($message, \Punic\Exception::INVALID_LOCALE, $previous);
    }

    /**
     * Retrieves the bad locale.
     */
    public function getLocale()
    {
        return $this->locale;
    }
}
