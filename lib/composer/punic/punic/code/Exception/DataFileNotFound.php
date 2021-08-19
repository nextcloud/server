<?php

namespace Punic\Exception;

/**
 * An exception raised when an data file has not been found.
 */
class DataFileNotFound extends \Punic\Exception
{
    protected $identifier;
    protected $locale;
    protected $fallbackLocale;

    /**
     * Initializes the instance.
     *
     * @param string $identifier The data file identifier
     * @param string $locale The preferred locale (if the data file is locale-specific)
     * @param string $fallbackLocale The fallback locale (if the data file is locale-specific)
     * @param \Exception $previous The previous exception used for the exception chaining
     */
    public function __construct($identifier, $locale = '', $fallbackLocale = '', $previous = null)
    {
        $this->identifier = $identifier;
        if (empty($locale) && empty($fallbackLocale)) {
            $this->locale = '';
            $this->fallbackLocale = '';
            $message = "Unable to find the data file '$identifier'";
        } else {
            $this->locale = $locale;
            $this->fallbackLocale = $fallbackLocale;
            if (@strcasecmp($locale, $fallbackLocale) === 0) {
                $message = "Unable to find the data file '$identifier' for '$locale'";
            } else {
                $message = "Unable to find the data file '$identifier', neither for '$locale' nor for '$fallbackLocale'";
            }
        }
        parent::__construct($message, \Punic\Exception::DATA_FILE_NOT_FOUND, $previous);
    }

    /**
     * Retrieves the bad data file identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Retrieves the preferred locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Retrieves the fallback locale.
     *
     * @return string
     */
    public function getFallbackLocale()
    {
        return $this->fallbackLocale;
    }
}
