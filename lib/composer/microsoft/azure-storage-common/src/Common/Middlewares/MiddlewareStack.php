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
 * @package   MicrosoftAzure\Storage\Common\Middlewares
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Middlewares;

/**
 * This class provides the stack that handles the logic of applying each
 * middlewares to the request or the response.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Middlewares
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class MiddlewareStack
{
    private $middlewares = array();

    /**
     * Push the given middleware into the middleware stack.
     *
     * @param  IMiddleware|callable $middleware The middleware to be pushed.
     *
     * @return void
     */
    public function push($middleware)
    {
        array_unshift($this->middlewares, $middleware);
    }

    /**
     * Apply the middlewares to the handler.
     *
     * @param  callable $handler the handler to which the middleware applies.
     *
     * @return callable
     */
    public function apply(callable $handler)
    {
        $result = $handler;
        foreach ($this->middlewares as $middleware) {
            $result = $middleware($result);
        }

        return $result;
    }
}
