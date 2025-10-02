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
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal;

use MicrosoftAzure\Storage\Common\Internal\IMiddleware;

/**
 * Base class for all REST proxies.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class RestProxy
{
    /**
     * @var array
     */
    private $middlewares;

    /**
     * @var Serialization\ISerializer
     */
    protected $dataSerializer;

    /**
     * Initializes new RestProxy object.
     *
     * @param Serialization\ISerializer $dataSerializer The data serializer.
     */
    public function __construct(Serialization\ISerializer $dataSerializer = null)
    {
        $this->middlewares    = array();
        $this->dataSerializer = $dataSerializer;
        //For logging the request and responses.
        // $this->middlewares[] = new HistoryMiddleware('.\\messages.log');
    }

    /**
     * Gets middlewares that will be handling the request and response.
     *
     * @return array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Push a new middleware into the middlewares array. The newly added
     * middleware will be the most inner middleware when executed.
     *
     * @param  callable|IMiddleware $middleware the middleware to be added.
     *
     * @return void
     */
    public function pushMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Adds optional query parameter.
     *
     * Doesn't add the value if it satisfies empty().
     *
     * @param array  &$queryParameters The query parameters.
     * @param string $key              The query variable name.
     * @param string $value            The query variable value.
     *
     * @return void
     */
    protected function addOptionalQueryParam(array &$queryParameters, $key, $value)
    {
        Validate::isArray($queryParameters, 'queryParameters');
        Validate::canCastAsString($key, 'key');
        Validate::canCastAsString($value, 'value');

        if (!is_null($value) && Resources::EMPTY_STRING !== $value) {
            $queryParameters[$key] = $value;
        }
    }

    /**
     * Adds optional header.
     *
     * Doesn't add the value if it satisfies empty().
     *
     * @param array  &$headers The HTTP header parameters.
     * @param string $key      The HTTP header name.
     * @param string $value    The HTTP header value.
     *
     * @return void
     */
    protected function addOptionalHeader(array &$headers, $key, $value)
    {
        Validate::isArray($headers, 'headers');
        Validate::canCastAsString($key, 'key');
        Validate::canCastAsString($value, 'value');

        if (!is_null($value) && Resources::EMPTY_STRING !== $value) {
            $headers[$key] = $value;
        }
    }
}
