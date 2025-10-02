<?php

/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal\Http
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal\Http;

use MicrosoftAzure\Storage\Common\Internal\Utilities;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\Validate;
use MicrosoftAzure\Storage\Common\Models\ServiceOptions;

/**
 * Holds basic elements for making HTTP call.
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal\Http
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class HttpCallContext
{
    private $_method;
    private $_headers;
    private $_queryParams;
    private $_postParameters;
    private $_uri;
    private $_path;
    private $_statusCodes;
    private $_body;
    private $_serviceOptions;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        $this->_method         = null;
        $this->_body           = null;
        $this->_path           = null;
        $this->_uri            = null;
        $this->_queryParams    = array();
        $this->_postParameters = array();
        $this->_statusCodes    = array();
        $this->_headers        = array();
        $this->_serviceOptions = new ServiceOptions();
    }

    /**
     * Gets method.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Sets method.
     *
     * @param string $method The method value.
     *
     * @return void
     */
    public function setMethod($method)
    {
        Validate::canCastAsString($method, 'method');

        $this->_method = $method;
    }

    /**
     * Gets headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Sets headers.
     *
     * Ignores the header if its value is empty.
     *
     * @param array $headers The headers value.
     *
     * @return void
     */
    public function setHeaders(array $headers)
    {
        $this->_headers = array();
        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }
    }

    /**
     * Gets queryParams.
     *
     * @return array
     */
    public function getQueryParameters()
    {
        return $this->_queryParams;
    }

    /**
     * Sets queryParams.
     *
     * Ignores the query variable if its value is empty.
     *
     * @param array $queryParams The queryParams value.
     *
     * @return void
     */
    public function setQueryParameters(array $queryParams)
    {
        $this->_queryParams = array();
        foreach ($queryParams as $key => $value) {
            $this->addQueryParameter($key, $value);
        }
    }

    /**
     * Gets uri.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * Sets uri.
     *
     * @param string $uri The uri value.
     *
     * @return void
     */
    public function setUri($uri)
    {
        Validate::canCastAsString($uri, 'uri');

        $this->_uri = $uri;
    }

    /**
     * Gets path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Sets path.
     *
     * @param string $path The path value.
     *
     * @return void
     */
    public function setPath($path)
    {
        Validate::canCastAsString($path, 'path');

        $this->_path = $path;
    }

    /**
     * Gets statusCodes.
     *
     * @return array
     */
    public function getStatusCodes()
    {
        return $this->_statusCodes;
    }

    /**
     * Sets statusCodes.
     *
     * @param array $statusCodes The statusCodes value.
     *
     * @return void
     */
    public function setStatusCodes(array $statusCodes)
    {
        $this->_statusCodes = array();
        foreach ($statusCodes as $value) {
            $this->addStatusCode($value);
        }
    }

    /**
     * Gets body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * Sets body.
     *
     * @param string $body The body value.
     *
     * @return void
     */
    public function setBody($body)
    {
        Validate::canCastAsString($body, 'body');

        $this->_body = $body;
    }

    /**
     * Adds or sets header pair.
     *
     * @param string $name  The HTTP header name.
     * @param string $value The HTTP header value.
     *
     * @return void
     */
    public function addHeader($name, $value)
    {
        Validate::canCastAsString($name, 'name');
        Validate::canCastAsString($value, 'value');

        $this->_headers[$name] = $value;
    }

    /**
     * Adds or sets header pair.
     *
     * Ignores header if it's value satisfies empty().
     *
     * @param string $name  The HTTP header name.
     * @param string $value The HTTP header value.
     *
     * @return void
     */
    public function addOptionalHeader($name, $value)
    {
        Validate::canCastAsString($name, 'name');
        Validate::canCastAsString($value, 'value');

        if (!empty($value)) {
            $this->_headers[$name] = $value;
        }
    }

    /**
     * Removes header from the HTTP request headers.
     *
     * @param string $name The HTTP header name.
     *
     * @return void
     */
    public function removeHeader($name)
    {
        Validate::canCastAsString($name, 'name');
        Validate::notNullOrEmpty($name, 'name');

        unset($this->_headers[$name]);
    }

    /**
     * Adds or sets query parameter pair.
     *
     * @param string $name  The URI query parameter name.
     * @param string $value The URI query parameter value.
     *
     * @return void
     */
    public function addQueryParameter($name, $value)
    {
        Validate::canCastAsString($name, 'name');
        Validate::canCastAsString($value, 'value');

        $this->_queryParams[$name] = $value;
    }

    /**
     * Gets HTTP POST parameters.
     *
     * @return array
     */
    public function getPostParameters()
    {
        return $this->_postParameters;
    }

    /**
     * Sets HTTP POST parameters.
     *
     * @param array $postParameters The HTTP POST parameters.
     *
     * @return void
     */
    public function setPostParameters(array $postParameters)
    {
        Validate::isArray($postParameters, 'postParameters');
        $this->_postParameters = $postParameters;
    }

    /**
     * Adds or sets query parameter pair.
     *
     * Ignores query parameter if it's value satisfies empty().
     *
     * @param string $name  The URI query parameter name.
     * @param string $value The URI query parameter value.
     *
     * @return void
     */
    public function addOptionalQueryParameter($name, $value)
    {
        Validate::canCastAsString($name, 'name');
        Validate::canCastAsString($value, 'value');

        if (!empty($value)) {
            $this->_queryParams[$name] = $value;
        }
    }

    /**
     * Adds status code to the expected status codes.
     *
     * @param integer $statusCode The expected status code.
     *
     * @return void
     */
    public function addStatusCode($statusCode)
    {
        Validate::isInteger($statusCode, 'statusCode');

        $this->_statusCodes[] = $statusCode;
    }

    /**
     * Gets header value.
     *
     * @param string $name The header name.
     *
     * @return mixed
     */
    public function getHeader($name)
    {
        return Utilities::tryGetValue($this->_headers, $name);
    }

    /**
     * Gets the saved service options
     *
     * @return ServiceOptions
     */
    public function getServiceOptions()
    {
        if ($this->_serviceOptions == null) {
            $this->_serviceOptions = new ServiceOptions();
        }
        return $this->_serviceOptions;
    }

    /**
     * Sets the service options
     *
     * @param ServiceOptions $serviceOptions the service options to be set.
     *
     * @return void
     */
    public function setServiceOptions(ServiceOptions $serviceOptions)
    {
        $this->_serviceOptions = $serviceOptions;
    }

    /**
     * Converts the context object to string.
     *
     * @return string
     */
    public function __toString()
    {
        $headers = Resources::EMPTY_STRING;
        $uri     = $this->_uri;

        if ($uri === null) {
            $uri = '/';
        } elseif ($uri[strlen($uri)-1] != '/') {
            $uri = $uri.'/';
        }

        foreach ($this->_headers as $key => $value) {
            $headers .= "$key: $value\n";
        }

        $str  = "$this->_method $uri$this->_path HTTP/1.1\n$headers\n";
        $str .= "$this->_body";

        return $str;
    }
}
