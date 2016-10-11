<?php

namespace Guzzle\Http\Curl;

/**
 * Class used for querying curl_version data
 */
class CurlVersion
{
    /** @var array curl_version() information */
    protected $version;

    /** @var CurlVersion */
    protected static $instance;

    /** @var string Default user agent */
    protected $userAgent;

    /**
     * @return CurlVersion
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get all of the curl_version() data
     *
     * @return array
     */
    public function getAll()
    {
        if (!$this->version) {
            $this->version = curl_version();
        }

        return $this->version;
    }

    /**
     * Get a specific type of curl information
     *
     * @param string $type Version information to retrieve. This value is one of:
     *     - version_number:     cURL 24 bit version number
     *     - version:            cURL version number, as a string
     *     - ssl_version_number: OpenSSL 24 bit version number
     *     - ssl_version:        OpenSSL version number, as a string
     *     - libz_version:       zlib version number, as a string
     *     - host:               Information about the host where cURL was built
     *     - features:           A bitmask of the CURL_VERSION_XXX constants
     *     - protocols:          An array of protocols names supported by cURL
     *
     * @return string|float|bool if the $type is found, and false if not found
     */
    public function get($type)
    {
        $version = $this->getAll();

        return isset($version[$type]) ? $version[$type] : false;
    }
}
