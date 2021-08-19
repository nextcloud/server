<?php

namespace Punic\Exception;

/**
 * An exception raised when an data folder has not been found.
 */
class DataFolderNotFound extends \Punic\Exception
{
    protected $locale;
    protected $fallbackLocale;

    /**
     * Initializes the instance.
     *
     * @param string $locale The preferred locale
     * @param string $fallbackLocale The fallback locale
     * @param \Exception $previous The previous exception used for the exception chaining
     */
    public function __construct($locale, $fallbackLocale, $previous = null)
    {
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;
        if (@strcasecmp($locale, $fallbackLocale) === 0) {
            $message = "Unable to find the specified locale folder for '$locale'";
        } else {
            $message = "Unable to find the specified locale folder, neither for '$locale' nor for '$fallbackLocale'";
        }
        parent::__construct($message, \Punic\Exception::DATA_FOLDER_NOT_FOUND, $previous);
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
