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
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal;

use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Internal\RetryMiddlewareFactory;
use MicrosoftAzure\Storage\Common\Internal\Serialization\XmlSerializer;
use MicrosoftAzure\Storage\Common\Models\ServiceOptions;
use MicrosoftAzure\Storage\Common\Internal\Http\HttpCallContext;
use MicrosoftAzure\Storage\Common\Internal\Middlewares\MiddlewareBase;
use MicrosoftAzure\Storage\Common\Middlewares\MiddlewareStack;
use MicrosoftAzure\Storage\Common\LocationMode;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;

/**
 * Base class for all services rest proxies.
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class ServiceRestProxy extends RestProxy
{
    private $accountName;
    private $psrPrimaryUri;
    private $psrSecondaryUri;
    private $options;
    private $client;

    /**
     * Initializes new ServiceRestProxy object.
     *
     * @param string                    $primaryUri     The storage account
     *                                                  primary uri.
     * @param string                    $secondaryUri   The storage account
     *                                                  secondary uri.
     * @param string                    $accountName    The name of the account.
     * @param array                     $options        Array of options for
     *                                                  the service
     */
    public function __construct(
        $primaryUri,
        $secondaryUri,
        $accountName,
        array $options = []
    ) {
        $primaryUri   = Utilities::appendDelimiter($primaryUri, '/');
        $secondaryUri = Utilities::appendDelimiter($secondaryUri, '/');

        $dataSerializer = new XmlSerializer();
        parent::__construct($dataSerializer);

        $this->accountName     = $accountName;
        $this->psrPrimaryUri   = new Uri($primaryUri);
        $this->psrSecondaryUri = new Uri($secondaryUri);
        $this->options         = array_merge(array('http' => array()), $options);
        $this->client          = self::createClient($this->options['http']);
    }

    /**
     * Create a Guzzle client for future usage.
     *
     * @param  array $options Optional parameters for the client.
     *
     * @return Client
     */
    private static function createClient(array $options)
    {
        $verify = true;
        //Disable SSL if proxy has been set, and set the proxy in the client.
        $proxy = getenv('HTTP_PROXY');
        // For testing with Fiddler
        // $proxy = 'localhost:8888';
        // $verify = false;
        if (!empty($proxy)) {
            $options['proxy'] = $proxy;
        }

        if (isset($options['verify'])) {
            $verify = $options['verify'];
        }

        return (new \GuzzleHttp\Client(
            array_merge(
                $options,
                array(
                    "defaults" => array(
                        "allow_redirects" => true,
                        "exceptions" => true,
                        "decode_content" => true,
                        "config" => [
                            "curl" => [
                                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2
                            ]
                        ]
                    ),
                    'cookies' => true,
                    'verify' => $verify,
                )
            )
        ));
    }

    /**
     * Gets the account name.
     *
     * @return string
     */
    public function getAccountName()
    {
        return $this->accountName;
    }

    /**
     * Create a middleware stack with given middleware.
     *
     * @param  ServiceOptions  $serviceOptions The options user passed in.
     *
     * @return MiddlewareStack
     */
    protected function createMiddlewareStack(ServiceOptions $serviceOptions)
    {
        //If handler stack is not defined by the user, create a default
        //middleware stack.
        $stack = null;
        if (array_key_exists('stack', $this->options['http'])) {
            $stack = $this->options['http']['stack'];
        } elseif ($serviceOptions->getMiddlewareStack() != null) {
            $stack = $serviceOptions->getMiddlewareStack();
        } else {
            $stack = new MiddlewareStack();
        }

        //Push all the middlewares specified in the $serviceOptions to the
        //handlerstack.
        if ($serviceOptions->getMiddlewares() != array()) {
            foreach ($serviceOptions->getMiddlewares() as $middleware) {
                $stack->push($middleware);
            }
        }

        //Push all the middlewares specified in the $options to the
        //handlerstack.
        if (array_key_exists('middlewares', $this->options)) {
            foreach ($this->options['middlewares'] as $middleware) {
                $stack->push($middleware);
            }
        }

        //Push all the middlewares specified in $this->middlewares to the
        //handlerstack.
        foreach ($this->getMiddlewares() as $middleware) {
            $stack->push($middleware);
        }

        return $stack;
    }

    /**
     * Send the requests concurrently. Number of concurrency can be modified
     * by inserting a new key/value pair with the key 'number_of_concurrency'
     * into the $requestOptions of $serviceOptions. Return only the promise.
     *
     * @param  callable       $generator   the generator function to generate
     *                                     request upon fulfillment
     * @param  int            $statusCode  The expected status code for each of the
     *                                     request generated by generator.
     * @param  ServiceOptions $options     The service options for the concurrent
     *                                     requests.
     *
     * @return \GuzzleHttp\Promise\Promise|\GuzzleHttp\Promise\PromiseInterface
     */
    protected function sendConcurrentAsync(
        callable $generator,
        $statusCode,
        ServiceOptions $options
    ) {
        $client = $this->client;
        $middlewareStack = $this->createMiddlewareStack($options);

        $sendAsync = function ($request, $options) use ($client) {
            if ($request->getMethod() == 'HEAD') {
                $options['decode_content'] = false;
            }
            return $client->sendAsync($request, $options);
        };

        $handler = $middlewareStack->apply($sendAsync);

        $requestOptions = $this->generateRequestOptions($options, $handler);

        $promises = \call_user_func(
            function () use (
                $generator,
                $handler,
                $requestOptions
            ) {
                while (is_callable($generator) && ($request = $generator())) {
                    yield \call_user_func($handler, $request, $requestOptions);
                }
            }
        );

        $eachPromise = new EachPromise($promises, [
            'concurrency' => $options->getNumberOfConcurrency(),
            'fulfilled' => function ($response, $index) use ($statusCode) {
                //the promise is fulfilled, evaluate the response
                self::throwIfError(
                    $response,
                    $statusCode
                );
            },
            'rejected' => function ($reason, $index) {
                //Still rejected even if the retry logic has been applied.
                //Throwing exception.
                throw $reason;
            }
        ]);

        return $eachPromise->promise();
    }


    /**
     * Create the request to be sent.
     *
     * @param  string $method         The method of the HTTP request
     * @param  array  $headers        The header field of the request
     * @param  array  $queryParams    The query parameter of the request
     * @param  array  $postParameters The HTTP POST parameters
     * @param  string $path           URL path
     * @param  string $body           Request body
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function createRequest(
        $method,
        array $headers,
        array $queryParams,
        array $postParameters,
        $path,
        $locationMode,
        $body = Resources::EMPTY_STRING
    ) {
        if ($locationMode == LocationMode::SECONDARY_ONLY ||
            $locationMode == LocationMode::SECONDARY_THEN_PRIMARY) {
            $uri = $this->psrSecondaryUri;
        } else {
            $uri = $this->psrPrimaryUri;
        }

        //Append the path, not replacing it.
        if ($path != null) {
            $exPath = $uri->getPath();
            if ($exPath != '') {
                //Remove the duplicated slash in the path.
                if ($path != '' && $path[0] == '/') {
                    $path = $exPath . substr($path, 1);
                } else {
                    $path = $exPath . $path;
                }
            }
            $uri = $uri->withPath($path);
        }

        // add query parameters into headers
        if ($queryParams != null) {
            $queryString = Psr7\Query::build($queryParams);
            $uri = $uri->withQuery($queryString);
        }

        // add post parameters into bodies
        $actualBody = null;
        if (empty($body)) {
            if (empty($headers[Resources::CONTENT_TYPE])) {
                $headers[Resources::CONTENT_TYPE] = Resources::URL_ENCODED_CONTENT_TYPE;
                $actualBody = Psr7\Query::build($postParameters);
            }
        } else {
            $actualBody = $body;
        }

        $request = new Request(
            $method,
            $uri,
            $headers,
            $actualBody
        );

        //add content-length to header
        $bodySize = $request->getBody()->getSize();
        if ($bodySize > 0) {
            $request = $request->withHeader('content-length', $bodySize);
        }
        return $request;
    }

    /**
     * Create promise of sending HTTP request with the specified parameters.
     *
     * @param  string         $method         HTTP method used in the request
     * @param  array          $headers        HTTP headers.
     * @param  array          $queryParams    URL query parameters.
     * @param  array          $postParameters The HTTP POST parameters.
     * @param  string         $path           URL path
     * @param  array|int      $expected       Expected Status Codes.
     * @param  string         $body           Request body
     * @param  ServiceOptions $serviceOptions Service options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    protected function sendAsync(
        $method,
        array $headers,
        array $queryParams,
        array $postParameters,
        $path,
        $expected = Resources::STATUS_OK,
        $body = Resources::EMPTY_STRING,
        ServiceOptions $serviceOptions = null
    ) {
        if ($serviceOptions == null) {
            $serviceOptions = new ServiceOptions();
        }
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_TIMEOUT,
            $serviceOptions->getTimeout()
        );

        $request = $this->createRequest(
            $method,
            $headers,
            $queryParams,
            $postParameters,
            $path,
            $serviceOptions->getLocationMode(),
            $body
        );

        $client = $this->client;

        $middlewareStack = $this->createMiddlewareStack($serviceOptions);

        $sendAsync = function ($request, $options) use ($client) {
            return $client->sendAsync($request, $options);
        };

        $handler = $middlewareStack->apply($sendAsync);

        $requestOptions =
            $this->generateRequestOptions($serviceOptions, $handler);

        if ($request->getMethod() == 'HEAD') {
            $requestOptions[Resources::ROS_DECODE_CONTENT] = false;
        }

        $promise = \call_user_func($handler, $request, $requestOptions);

        return $promise->then(
            function ($response) use ($expected, $requestOptions) {
                self::throwIfError(
                    $response,
                    $expected
                );

                return self::addLocationHeaderToResponse(
                    $response,
                    $requestOptions[Resources::ROS_LOCATION_MODE]
                );
            },
            function ($reason) use ($expected) {
                return $this->onRejected($reason, $expected);
            }
        );
    }

    /**
     * @param  string|\Exception $reason   Rejection reason.
     * @param  array|int         $expected Expected Status Codes.
     *
     * @return ResponseInterface
     */
    protected function onRejected($reason, $expected)
    {
        if (!($reason instanceof \Exception)) {
            throw new \RuntimeException($reason);
        }
        if (!($reason instanceof RequestException)) {
            throw $reason;
        }
        $response = $reason->getResponse();
        if ($response != null) {
            self::throwIfError(
                $response,
                $expected
            );
        } else {
            //if could not get response but promise rejected, throw reason.
            throw $reason;
        }
        return $response;
    }

    /**
     * Generate the request options using the given service options and stored
     * information.
     *
     * @param  ServiceOptions $serviceOptions The service options used to
     *                                        generate request options.
     * @param  callable       $handler        The handler used to send the
     *                                        request.
     * @return array
     */
    protected function generateRequestOptions(
        ServiceOptions $serviceOptions,
        callable $handler
    ) {
        $result = array();
        $result[Resources::ROS_LOCATION_MODE]  = $serviceOptions->getLocationMode();
        $result[Resources::ROS_STREAM]         = $serviceOptions->getIsStreaming();
        $result[Resources::ROS_DECODE_CONTENT] = $serviceOptions->getDecodeContent();
        $result[Resources::ROS_HANDLER]        = $handler;
        $result[Resources::ROS_SECONDARY_URI]  = $this->getPsrSecondaryUri();
        $result[Resources::ROS_PRIMARY_URI]    = $this->getPsrPrimaryUri();

        return $result;
    }

    /**
     * Sends the context.
     *
     * @param  HttpCallContext $context The context of the request.
     * @return \GuzzleHttp\Psr7\Response
     */
    protected function sendContext(HttpCallContext $context)
    {
        return $this->sendContextAsync($context)->wait();
    }

    /**
     * Creates the promise to send the context.
     *
     * @param  HttpCallContext $context The context of the request.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    protected function sendContextAsync(HttpCallContext $context)
    {
        return $this->sendAsync(
            $context->getMethod(),
            $context->getHeaders(),
            $context->getQueryParameters(),
            $context->getPostParameters(),
            $context->getPath(),
            $context->getStatusCodes(),
            $context->getBody(),
            $context->getServiceOptions()
        );
    }

    /**
     * Throws ServiceException if the received status code is not expected.
     *
     * @param ResponseInterface $response The response received
     * @param array|int         $expected The expected status codes.
     *
     * @return void
     *
     * @throws ServiceException
     */
    public static function throwIfError(ResponseInterface $response, $expected)
    {
        $expectedStatusCodes = is_array($expected) ? $expected : array($expected);

        if (!in_array($response->getStatusCode(), $expectedStatusCodes)) {
            throw new ServiceException($response);
        }
    }

    /**
     * Adds HTTP POST parameter to the specified
     *
     * @param array  $postParameters An array of HTTP POST parameters.
     * @param string $key            The key of a HTTP POST parameter.
     * @param string $value          the value of a HTTP POST parameter.
     *
     * @return array
     */
    public function addPostParameter(
        array $postParameters,
        $key,
        $value
    ) {
        Validate::isArray($postParameters, 'postParameters');
        $postParameters[$key] = $value;
        return $postParameters;
    }

    /**
     * Groups set of values into one value separated with Resources::SEPARATOR
     *
     * @param array $values array of values to be grouped.
     *
     * @return string
     */
    public static function groupQueryValues(array $values)
    {
        Validate::isArray($values, 'values');
        $joined = Resources::EMPTY_STRING;

        sort($values);

        foreach ($values as $value) {
            if (!is_null($value) && !empty($value)) {
                $joined .= $value . Resources::SEPARATOR;
            }
        }

        return trim($joined, Resources::SEPARATOR);
    }

    /**
     * Adds metadata elements to headers array
     *
     * @param array $headers  HTTP request headers
     * @param array $metadata user specified metadata
     *
     * @return array
     */
    protected function addMetadataHeaders(array $headers, array $metadata = null)
    {
        Utilities::validateMetadata($metadata);

        $metadata = $this->generateMetadataHeaders($metadata);
        $headers  = array_merge($headers, $metadata);

        return $headers;
    }

    /**
     * Generates metadata headers by prefixing each element with 'x-ms-meta'.
     *
     * @param array $metadata user defined metadata.
     *
     * @return array
     */
    public function generateMetadataHeaders(array $metadata = null)
    {
        $metadataHeaders = array();

        if (is_array($metadata) && !is_null($metadata)) {
            foreach ($metadata as $key => $value) {
                $headerName = Resources::X_MS_META_HEADER_PREFIX;
                if (strpos($value, "\r") !== false
                    || strpos($value, "\n") !== false
                ) {
                    throw new \InvalidArgumentException(Resources::INVALID_META_MSG);
                }

                // Metadata name is case-presrved and case insensitive
                $headerName                     .= $key;
                $metadataHeaders[$headerName] = $value;
            }
        }

        return $metadataHeaders;
    }

    /**
     * Get the primary URI in PSR form.
     *
     * @return Uri
     */
    public function getPsrPrimaryUri()
    {
        return $this->psrPrimaryUri;
    }

    /**
     * Get the secondary URI in PSR form.
     *
     * @return Uri
     */
    public function getPsrSecondaryUri()
    {
        return $this->psrSecondaryUri;
    }

    /**
     * Adds the header that indicates the location mode to the response header.
     *
     * @return  ResponseInterface
     */
    private static function addLocationHeaderToResponse(
        ResponseInterface $response,
        $locationMode
    ) {
        //If the response already has this header, return itself.
        if ($response->hasHeader(Resources::X_MS_CONTINUATION_LOCATION_MODE)) {
            return $response;
        }
        //Otherwise, add the header that indicates the endpoint to be used if
        //continuation token is used for subsequent request. Notice that if the
        //response does not have location header set at the moment, it means
        //that the user have not set a retry middleware.
        if ($locationMode == LocationMode::PRIMARY_THEN_SECONDARY) {
            $response = $response->withHeader(
                Resources::X_MS_CONTINUATION_LOCATION_MODE,
                LocationMode::PRIMARY_ONLY
            );
        } elseif ($locationMode == LocationMode::SECONDARY_THEN_PRIMARY) {
            $response = $response->withHeader(
                Resources::X_MS_CONTINUATION_LOCATION_MODE,
                LocationMode::SECONDARY_ONLY
            );
        } elseif ($locationMode == LocationMode::SECONDARY_ONLY  ||
                  $locationMode == LocationMode::PRIMARY_ONLY) {
            $response = $response->withHeader(
                Resources::X_MS_CONTINUATION_LOCATION_MODE,
                $locationMode
            );
        }
        return $response;
    }
}
