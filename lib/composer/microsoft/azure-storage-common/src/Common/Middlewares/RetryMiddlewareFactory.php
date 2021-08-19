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
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Middlewares;

use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\Validate;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * This class provides static functions that creates retry handlers for Guzzle
 * HTTP clients to handle retry policy.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Middlewares
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class RetryMiddlewareFactory
{
    //The interval will be increased linearly, the nth retry will have a
    //wait time equal to n * interval.
    const LINEAR_INTERVAL_ACCUMULATION      = 'Linear';
    //The interval will be increased exponentially, the nth retry will have a
    //wait time equal to pow(2, n) * interval.
    const EXPONENTIAL_INTERVAL_ACCUMULATION = 'Exponential';
    //This is for the general type of logic that handles retry.
    const GENERAL_RETRY_TYPE                = 'General';
    //This is for the append blob retry only.
    const APPEND_BLOB_RETRY_TYPE            = 'Append Blob Retry';

    /**
     * Create the retry handler for the Guzzle client, according to the given
     * attributes.
     *
     * @param  string $type                The type that controls the logic of
     *                                     the decider of the retry handler.
     *                                     Possible value can be
     *                                     self::GENERAL_RETRY_TYPE or
     *                                     self::APPEND_BLOB_RETRY_TYPE
     * @param  int    $numberOfRetries     The maximum number of retries.
     * @param  int    $interval            The minimum interval between each retry
     * @param  string $accumulationMethod  If the interval increases linearly or
     *                                     exponentially.
     *                                     Possible value can be
     *                                     self::LINEAR_INTERVAL_ACCUMULATION or
     *                                     self::EXPONENTIAL_INTERVAL_ACCUMULATION
     * @param  bool   $retryConnect        Whether to retry on connection failures.
     * @return RetryMiddleware             A RetryMiddleware object that contains
     *                                     the logic of how the request should be
     *                                     handled after a response.
     */
    public static function create(
        $type = self::GENERAL_RETRY_TYPE,
        $numberOfRetries = Resources::DEFAULT_NUMBER_OF_RETRIES,
        $interval = Resources::DEFAULT_RETRY_INTERVAL,
        $accumulationMethod = self::LINEAR_INTERVAL_ACCUMULATION,
        $retryConnect = false
    ) {
        //Validate the input parameters
        //type
        Validate::isTrue(
            $type == self::GENERAL_RETRY_TYPE ||
            $type == self::APPEND_BLOB_RETRY_TYPE,
            sprintf(
                Resources::INVALID_PARAM_GENERAL,
                'type'
            )
        );
        //numberOfRetries
        Validate::isTrue(
            $numberOfRetries > 0,
            sprintf(
                Resources::INVALID_NEGATIVE_PARAM,
                'numberOfRetries'
            )
        );
        //interval
        Validate::isTrue(
            $interval > 0,
            sprintf(
                Resources::INVALID_NEGATIVE_PARAM,
                'interval'
            )
        );
        //accumulationMethod
        Validate::isTrue(
            $accumulationMethod == self::LINEAR_INTERVAL_ACCUMULATION ||
            $accumulationMethod == self::EXPONENTIAL_INTERVAL_ACCUMULATION,
            sprintf(
                Resources::INVALID_PARAM_GENERAL,
                'accumulationMethod'
            )
        );
        //retryConnect
        Validate::isBoolean($retryConnect);

        //Get the interval calculator according to the type of the
        //accumulation method.
        $intervalCalculator =
            $accumulationMethod == self::LINEAR_INTERVAL_ACCUMULATION ?
            static::createLinearDelayCalculator($interval) :
            static::createExponentialDelayCalculator($interval);

        //Get the retry decider according to the type of the retry and
        //the number of retries.
        $retryDecider = static::createRetryDecider($type, $numberOfRetries, $retryConnect);

        //construct the retry middle ware.
        return new RetryMiddleware($intervalCalculator, $retryDecider);
    }

    /**
     * Create the retry decider for the retry handler. It will return a callable
     * that accepts the number of retries, the request, the response and the
     * exception, and return the decision for a retry.
     *
     * @param  string $type         The type of the retry handler.
     * @param  int    $maxRetries   The maximum number of retries to be done.
     * @param  bool   $retryConnect Whether to retry on connection failures.
     *
     * @return callable     The callable that will return if the request should
     *                      be retried.
     */
    protected static function createRetryDecider($type, $maxRetries, $retryConnect)
    {
        return function (
            $retries,
            $request,
            $response = null,
            $exception = null,
            $isSecondary = false
        ) use (
            $type,
            $maxRetries,
            $retryConnect
        ) {
            //Exceeds the retry limit. No retry.
            if ($retries >= $maxRetries) {
                return false;
            }

            if (!$response) {
                if (!$exception || !($exception instanceof RequestException)) {
                    return false;
                } elseif ($exception instanceof ConnectException) {
                    return $retryConnect;
                } else {
                    $response = $exception->getResponse();
                    if (!$response) {
                        return true;
                    }
                }
            }

            if ($type == self::GENERAL_RETRY_TYPE) {
                return static::generalRetryDecider(
                    $response->getStatusCode(),
                    $isSecondary
                );
            } else {
                return static::appendBlobRetryDecider(
                    $response->getStatusCode(),
                    $isSecondary
                );
            }

            return true;
        };
    }

    /**
     * Decide if the given status code indicate the request should be retried.
     *
     * @param  int  $statusCode  Status code of the previous request.
     * @param  bool $isSecondary Whether the request is sent to secondary endpoint.
     *
     * @return bool            true if the request should be retried.
     */
    protected static function generalRetryDecider($statusCode, $isSecondary)
    {
        $retry = false;
        if ($statusCode == 408) {
            $retry = true;
        } elseif ($statusCode >= 500) {
            if ($statusCode != 501 && $statusCode != 505) {
                $retry = true;
            }
        } elseif ($isSecondary && $statusCode == 404) {
            $retry = true;
        }
        return $retry;
    }

    /**
     * Decide if the given status code indicate the request should be retried.
     * This is for append blob.
     *
     * @param  int  $statusCode  Status code of the previous request.
     * @param  bool $isSecondary Whether the request is sent to secondary endpoint.
     *
     * @return bool            true if the request should be retried.
     */
    protected static function appendBlobRetryDecider($statusCode, $isSecondary)
    {
        //The retry logic is different for append blob.
        //First it will need to record the former status code if it is
        //server error. Then if the following request is 412 then it
        //needs to be retried. Currently this is not implemented so will
        //only adapt to the general retry decider.
        //TODO: add logic for append blob's retry when implemented.
        $retry = static::generalRetryDecider($statusCode, $isSecondary);
        return $retry;
    }

    /**
     * Create the delay calculator that increases the interval linearly
     * according to the number of retries.
     *
     * @param  int $interval the minimum interval of the retry.
     *
     * @return callable      a calculator that will return the interval
     *                       according to the number of retries.
     */
    protected static function createLinearDelayCalculator($interval)
    {
        return function ($retries) use ($interval) {
            return $retries * $interval;
        };
    }

    /**
     * Create the delay calculator that increases the interval exponentially
     * according to the number of retries.
     *
     * @param  int $interval the minimum interval of the retry.
     *
     * @return callable      a calculator that will return the interval
     *                       according to the number of retries.
     */
    protected static function createExponentialDelayCalculator($interval)
    {
        return function ($retries) use ($interval) {
            return $interval * ((int)\pow(2, $retries));
        };
    }
}
