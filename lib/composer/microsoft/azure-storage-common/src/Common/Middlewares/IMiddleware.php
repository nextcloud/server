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
 * IMiddleware is called before sending the request and after receiving the
 * response.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Middlewares
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

interface IMiddleware
{
    /**
     * This function will return a callable with $request and $options as
     * its parameters and returns a promise. The callable can modify the
     * request, fulfilled response or rejected reason when invoked with certain
     * conditions. Sample middleware implementation:
     *
     * ```
     * return function (
     *    RequestInterface $request,
     *    array $options
     * ) use ($handler) {
     *    //do something prior to sending the request.
     *    $promise = $handler($request, $options);
     *    return $promise->then(
     *        function (ResponseInterface $response) use ($request, $options) {
     *            //do something
     *            return $response;
     *        },
     *        function ($reason) use ($request, $options) {
     *            //do something
     *            return new GuzzleHttp\Promise\RejectedPromise($reason);
     *        }
     *    );
     * };
     * ```
     *
     * @param  callable $handler The next handler.
     * @return callable
     */
    public function __invoke(callable $handler);
}
