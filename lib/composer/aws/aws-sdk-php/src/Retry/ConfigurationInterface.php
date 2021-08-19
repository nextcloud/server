<?php
namespace Aws\Retry;

/**
 * Provides access to retry configuration
 */
interface ConfigurationInterface
{
    /**
     * Returns the retry mode. Available modes include 'legacy', 'standard', and
     * 'adapative'.
     *
     * @return string
     */
    public function getMode();

    /**
     * Returns the maximum number of attempts that will be used for a request
     *
     * @return string
     */
    public function getMaxAttempts();

    /**
     * Returns the configuration as an associative array
     *
     * @return array
     */
    public function toArray();
}
