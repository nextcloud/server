<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing;

/**
 * Holds information about the current request.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class RequestContext
{
    private $baseUrl;
    private $method;
    private $host;
    private $scheme;
    private $httpPort;
    private $httpsPort;
    private $parameters;

    /**
     * Constructor.
     *
     * @param string  $baseUrl   The base URL
     * @param string  $method    The HTTP method
     * @param string  $host      The HTTP host name
     * @param string  $scheme    The HTTP scheme
     * @param integer $httpPort  The HTTP port
     * @param integer $httpsPort The HTTPS port
     *
     * @api
     */
    public function __construct($baseUrl = '', $method = 'GET', $host = 'localhost', $scheme = 'http', $httpPort = 80, $httpsPort = 443)
    {
        $this->baseUrl = $baseUrl;
        $this->method = strtoupper($method);
        $this->host = $host;
        $this->scheme = strtolower($scheme);
        $this->httpPort = $httpPort;
        $this->httpsPort = $httpsPort;
        $this->parameters = array();
    }

    /**
     * Gets the base URL.
     *
     * @return string The base URL
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Sets the base URL.
     *
     * @param string $baseUrl The base URL
     *
     * @api
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Gets the HTTP method.
     *
     * The method is always an uppercased string.
     *
     * @return string The HTTP method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Sets the HTTP method.
     *
     * @param string $method The HTTP method
     *
     * @api
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }

    /**
     * Gets the HTTP host.
     *
     * @return string The HTTP host
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the HTTP host.
     *
     * @param string $host The HTTP host
     *
     * @api
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Gets the HTTP scheme.
     *
     * @return string The HTTP scheme
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Sets the HTTP scheme.
     *
     * @param string $scheme The HTTP scheme
     *
     * @api
     */
    public function setScheme($scheme)
    {
        $this->scheme = strtolower($scheme);
    }

    /**
     * Gets the HTTP port.
     *
     * @return string The HTTP port
     */
    public function getHttpPort()
    {
        return $this->httpPort;
    }

    /**
     * Sets the HTTP port.
     *
     * @param string $httpPort The HTTP port
     *
     * @api
     */
    public function setHttpPort($httpPort)
    {
        $this->httpPort = $httpPort;
    }

    /**
     * Gets the HTTPS port.
     *
     * @return string The HTTPS port
     */
    public function getHttpsPort()
    {
        return $this->httpsPort;
    }

    /**
     * Sets the HTTPS port.
     *
     * @param string $httpsPort The HTTPS port
     *
     * @api
     */
    public function setHttpsPort($httpsPort)
    {
        $this->httpsPort = $httpsPort;
    }

    /**
     * Returns the parameters.
     *
     * @return array The parameters
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Sets the parameters.
     *
     * This method implements a fluent interface.
     *
     * @param array $parameters The parameters
     *
     * @return Route The current Route instance
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Gets a parameter value.
     *
     * @param string $name A parameter name
     *
     * @return mixed The parameter value
     */
    public function getParameter($name)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * Checks if a parameter value is set for the given parameter.
     *
     * @param string $name A parameter name
     *
     * @return Boolean true if the parameter value is set, false otherwise
     */
    public function hasParameter($name)
    {
        return array_key_exists($name, $this->parameters);
    }

    /**
     * Sets a parameter value.
     *
     * @param string $name      A parameter name
     * @param mixed  $parameter The parameter value
     *
     * @api
     */
    public function setParameter($name, $parameter)
    {
        $this->parameters[$name] = $parameter;
    }
}
