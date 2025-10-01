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
 * @package   MicrosoftAzure\Storage\Common\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Models;

use MicrosoftAzure\Storage\Common\LocationMode;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\Validate;
use MicrosoftAzure\Storage\Common\Middlewares\MiddlewareStack;
use MicrosoftAzure\Storage\Common\Middlewares\IMiddleware;

/**
 * This class provides the base structure of service options, granting user to
 * send with different options for each individual API call.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class ServiceOptions
{
    /**
     * The middlewares to be applied using the operation.
     * @internal
     */
    protected $middlewares;

    /**
     * The middleware stack used for the operation.
     * @internal
     */
    protected $middlewareStack;

    /**
     * The number of concurrency when performing concurrent requests.
     * @internal
     */
    protected $numberOfConcurrency;

    /**
     * If streamming is used for the operation.
     * @internal
     */
    protected $isStreaming;

    /**
     * The location mode of the operation.
     * @internal
     */
    protected $locationMode;

    /**
     * If to decode the content of the response body.
     * @internal
     */
    protected $decodeContent;

    /**
     * The timeout of the operation
     * @internal
     */
    protected $timeout;

    /**
     * Initialize the properties to default value.
     */
    public function __construct(ServiceOptions $options = null)
    {
        if ($options == null) {
            $this->setNumberOfConcurrency(Resources::NUMBER_OF_CONCURRENCY);
            $this->setLocationMode(LocationMode::PRIMARY_ONLY);
            $this->setIsStreaming(false);
            $this->setDecodeContent(false);
            $this->middlewares = array();
            $this->middlewareStack = null;
        } else {
            $this->setNumberOfConcurrency($options->getNumberOfConcurrency());
            $this->setLocationMode($options->getLocationMode());
            $this->setIsStreaming($options->getIsStreaming());
            $this->setDecodeContent($options->getDecodeContent());
            $this->middlewares = $options->getMiddlewares();
            $this->middlewareStack = $options->getMiddlewareStack();
        }
    }

    /**
     * Push a middleware into the middlewares.
     * @param  callable|IMiddleware $middleware middleware to be pushed.
     *
     * @return void
     */
    public function pushMiddleware($middleware)
    {
        self::validateIsMiddleware($middleware);
        $this->middlewares[] = $middleware;
    }

    /**
     * Gets the middlewares.
     *
     * @return array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Sets middlewares.
     *
     * @param array $middlewares value.
     *
     * @return void
     */
    public function setMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            self::validateIsMiddleware($middleware);
        }
        $this->middlewares = $middlewares;
    }

    /**
     * Gets the middleware stack
     *
     * @return MiddlewareStack
     */
    public function getMiddlewareStack()
    {
        return $this->middlewareStack;
    }

    /**
     * Sets the middleware stack.
     *
     * @param MiddlewareStack $middlewareStack value.
     *
     * @return void
     */
    public function setMiddlewareStack(MiddlewareStack $middlewareStack)
    {
        $this->middlewareStack = $middlewareStack;
    }

    /**
     * Gets the number of concurrency value
     *
     * @return int
     */
    public function getNumberOfConcurrency()
    {
        return $this->numberOfConcurrency;
    }

    /**
     * Sets number of concurrency.
     *
     * @param int $numberOfConcurrency value.
     *
     * @return void
     */
    public function setNumberOfConcurrency($numberOfConcurrency)
    {
        $this->numberOfConcurrency = $numberOfConcurrency;
    }

    /**
     * Gets the isStreaming value
     *
     * @return bool
     */
    public function getIsStreaming()
    {
        return $this->isStreaming;
    }

    /**
     * Sets isStreaming.
     *
     * @param bool $isStreaming value.
     *
     * @return void
     */
    public function setIsStreaming($isStreaming)
    {
        $this->isStreaming = $isStreaming;
    }

    /**
     * Gets the locationMode value
     *
     * @return string
     */
    public function getLocationMode()
    {
        return $this->locationMode;
    }

    /**
     * Sets locationMode.
     *
     * @param string $locationMode value.
     *
     * @return void
     */
    public function setLocationMode($locationMode)
    {
        $this->locationMode = $locationMode;
    }

    /**
     * Gets the decodeContent value
     *
     * @return bool
     */
    public function getDecodeContent()
    {
        return $this->decodeContent;
    }

    /**
     * Sets decodeContent.
     *
     * @param bool $decodeContent value.
     *
     * @return void
     */
    public function setDecodeContent($decodeContent)
    {
        $this->decodeContent = $decodeContent;
    }

    /**
     * Gets the timeout value
     *
     * @return string
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Sets timeout.
     *
     * @param string $timeout value.
     *
     * @return void
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Generate request options using the input options and saved properties.
     *
     * @param  array  $options The options to be merged for the request options.
     *
     * @return array
     */
    public function generateRequestOptions(array $options)
    {
        $result = array();

        return $result;
    }

    /**
     * Validate if the given middleware is of callable or IMiddleware.
     *
     * @param  void $middleware the middleware to be validated.
     *
     * @return void
     */
    private static function validateIsMiddleware($middleware)
    {
        if (!(is_callable($middleware) || $middleware instanceof IMiddleware)) {
            Validate::isTrue(
                false,
                Resources::INVALID_TYPE_MSG . 'callable or IMiddleware'
            );
        }
    }
}
