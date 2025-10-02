<?php

namespace Aws\EndpointV2\Ruleset;

/**
 * Represents a fully resolved endpoint that a
 * rule returns if input parameters meet its requirements.
 */
class RulesetEndpoint
{
    /** @var string */
    private $url;

    /** @var array */
    private $properties;

    /** @var array */
    private $headers;

    public function __construct($url, $properties = null, $headers = null)
    {
        $this->url = $url;
        $this->properties = $properties;
        $this->headers = $headers;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param $property
     * @return mixed
     */
    public function getProperty($property)
    {
        if (isset($this->properties[$property])) {
            return $this->properties[$property];
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
