<?php
namespace Aws\DefaultsMode;

/**
 * Provides access to defaultsMode configuration
 */
interface ConfigurationInterface
{
    /**
     * Returns the configuration mode. Available modes include 'legacy', 'standard', and
     * 'adapative'.
     *
     * @return string
     */
    public function getMode();

    /**
     * Returns the sts regional endpoints option
     *
     * @return bool
     */
    public function getStsRegionalEndpoints();

    /**
     * Returns the s3 us-east-1 regional endpoints option
     *
     * @return bool
     */
    public function getS3UsEast1RegionalEndpoints();

    /**
     * Returns the connection timeout in milliseconds
     *
     * @return int
     */
    public function getConnectTimeoutInMillis();

    /**
     * Returns the http request timeout in milliseconds
     *
     * @return int
     */
    public function getHttpRequestTimeoutInMillis();

    /**
     * Returns the configuration as an associative array
     *
     * @return array
     */
    public function toArray();
}
