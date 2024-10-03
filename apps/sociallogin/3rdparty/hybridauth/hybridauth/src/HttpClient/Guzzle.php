<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\HttpClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;

/**
 * Hybridauth Guzzle Http client
 *
 * Note: This is just a proof of concept. Feel free to improve it.
 *
 * Example:
 *
 * <code>
 *  $guzzle = new Hybridauth\HttpClient\Guzzle(new GuzzleHttp\Client(), [
 *      'verify' => '/path/to/your/certificate.crt',
 *      'headers' => ['User-Agent' => '..']
 *      // 'proxy' => ...
 *  ]);
 *
 *  $adapter = new Hybridauth\Provider\Github($config, $guzzle);
 *
 *  $adapter->authenticate();
 * </code>
 */
class Guzzle implements HttpClientInterface
{
    /**
     * Method request() arguments
     *
     * This is used for debugging.
     *
     * @var array
     */
    protected $requestArguments = [];

    /**
     * Default request headers
     *
     * @var array
     */
    protected $requestHeader = [];

    /**
     * Raw response returned by server
     *
     * @var string
     */
    protected $responseBody = '';

    /**
     * Headers returned in the response
     *
     * @var array
     */
    protected $responseHeader = [];

    /**
     * Response HTTP status code
     *
     * @var int
     */
    protected $responseHttpCode = 0;

    /**
     * Last curl error number
     *
     * @var mixed
     */
    protected $responseClientError = null;

    /**
     * Information about the last transfer
     *
     * @var mixed
     */
    protected $responseClientInfo = [];

    /**
     * Hybridauth logger instance
     *
     * @var object
     */
    protected $logger = null;

    /**
     * GuzzleHttp client
     *
     * @var \GuzzleHttp\Client
     */
    protected $client = null;

    /**
     * ..
     * @param null $client
     * @param array $config
     */
    public function __construct($client = null, $config = [])
    {
        $this->client = $client ? $client : new Client($config);
    }

    /**
     * {@inheritdoc}
     */
    public function request($uri, $method = 'GET', $parameters = [], $headers = [], $multipart = false)
    {
        $this->requestHeader = array_replace($this->requestHeader, (array)$headers);

        $this->requestArguments = [
            'uri' => $uri,
            'method' => $method,
            'parameters' => $parameters,
            'headers' => $this->requestHeader,
        ];

        $response = null;

        try {
            switch ($method) {
                case 'GET':
                case 'DELETE':
                    $response = $this->client->request($method, $uri, [
                        'query' => $parameters,
                        'headers' => $this->requestHeader,
                    ]);
                    break;
                case 'PUT':
                case 'PATCH':
                case 'POST':
                    $body_type = $multipart ? 'multipart' : 'form_params';

                    if (isset($this->requestHeader['Content-Type'])
                        && $this->requestHeader['Content-Type'] === 'application/json'
                    ) {
                        $body_type = 'json';
                    }

                    $body_content = $parameters;
                    if ($multipart) {
                        $body_content = [];
                        foreach ($parameters as $key => $val) {
                            if ($val instanceof \CURLFile) {
                                $val = fopen($val->getFilename(), 'r');
                            }

                            $body_content[] = [
                                'name' => $key,
                                'contents' => $val,
                            ];
                        }
                    }

                    $response = $this->client->request($method, $uri, [
                        $body_type => $body_content,
                        'headers' => $this->requestHeader,
                    ]);
                    break;
            }
        } catch (\Exception $e) {
            $response = $e->getResponse();

            $this->responseClientError = $e->getMessage();
        }

        if (!$this->responseClientError) {
            $this->responseBody = $response->getBody();
            $this->responseHttpCode = $response->getStatusCode();
            $this->responseHeader = $response->getHeaders();
        }

        if ($this->logger) {
            // phpcs:ignore
            $this->logger->debug(sprintf('%s::request( %s, %s ), response:', get_class($this), $uri, $method), $this->getResponse());

            if ($this->responseClientError) {
                // phpcs:ignore
                $this->logger->error(sprintf('%s::request( %s, %s ), error:', get_class($this), $uri, $method), [$this->responseClientError]);
            }
        }

        return $this->responseBody;
    }

    /**
     * Get response details
     *
     * @return array Map structure of details
     */
    public function getResponse()
    {
        return [
            'request' => $this->getRequestArguments(),
            'response' => [
                'code' => $this->getResponseHttpCode(),
                'headers' => $this->getResponseHeader(),
                'body' => $this->getResponseBody(),
            ],
            'client' => [
                'error' => $this->getResponseClientError(),
                'info' => $this->getResponseClientInfo(),
                'opts' => null,
            ],
        ];
    }

    /**
     * Set logger instance
     *
     * @param object $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseHeader()
    {
        return $this->responseHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseHttpCode()
    {
        return $this->responseHttpCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseClientError()
    {
        return $this->responseClientError;
    }

    /**
     * @return array
     */
    protected function getResponseClientInfo()
    {
        return $this->responseClientInfo;
    }

    /**
     * Returns method request() arguments
     *
     * This is used for debugging.
     *
     * @return array
     */
    protected function getRequestArguments()
    {
        return $this->requestArguments;
    }
}
