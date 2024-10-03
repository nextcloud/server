<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\HttpClient;

/**
 * Hybridauth default Http client
 */
class Curl implements HttpClientInterface
{
    /**
     * Default curl options
     *
     * These defaults options can be overwritten when sending requests.
     *
     * See setCurlOptions()
     *
     * @var array
     */
    protected $curlOptions = [
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_ENCODING => 'identity',
        // phpcs:ignore
        CURLOPT_USERAGENT => 'Hybridauth, PHP Social Authentication Library (https://github.com/hybridauth/hybridauth)',
    ];

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
    protected $requestHeader = [
        'Accept' => '*/*',
        'Cache-Control' => 'max-age=0',
        'Connection' => 'keep-alive',
        'Expect' => '',
        'Pragma' => '',
    ];

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

        $curl = curl_init();

        switch ($method) {
            case 'GET':
            case 'DELETE':
                unset($this->curlOptions[CURLOPT_POST]);
                unset($this->curlOptions[CURLOPT_POSTFIELDS]);

                $uri = $uri . (strpos($uri, '?') ? '&' : '?') . http_build_query($parameters);
                if ($method === 'DELETE') {
                    $this->curlOptions[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                }
                break;
            case 'PUT':
            case 'POST':
            case 'PATCH':
                $body_content = $multipart ? $parameters : http_build_query($parameters);
                if (isset($this->requestHeader['Content-Type'])
                    && $this->requestHeader['Content-Type'] == 'application/json'
                ) {
                    $body_content = json_encode($parameters);
                }

                if ($method === 'POST') {
                    $this->curlOptions[CURLOPT_POST] = true;
                } else {
                    $this->curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
                }
                $this->curlOptions[CURLOPT_POSTFIELDS] = $body_content;
                break;
        }

        $this->curlOptions[CURLOPT_URL] = $uri;
        $this->curlOptions[CURLOPT_HTTPHEADER] = $this->prepareRequestHeaders();
        $this->curlOptions[CURLOPT_HEADERFUNCTION] = [$this, 'fetchResponseHeader'];

        foreach ($this->curlOptions as $opt => $value) {
            curl_setopt($curl, $opt, $value);
        }

        $response = curl_exec($curl);

        $this->responseBody = $response;
        $this->responseHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->responseClientError = curl_error($curl);
        $this->responseClientInfo = curl_getinfo($curl);

        if ($this->logger) {
            // phpcs:ignore
            $this->logger->debug(sprintf('%s::request( %s, %s ), response:', get_class($this), $uri, $method), $this->getResponse());

            if (false === $response) {
                // phpcs:ignore
                $this->logger->error(sprintf('%s::request( %s, %s ), error:', get_class($this), $uri, $method), [$this->responseClientError]);
            }
        }

        curl_close($curl);

        return $this->responseBody;
    }

    /**
     * Get response details
     *
     * @return array Map structure of details
     */
    public function getResponse()
    {
        $curlOptions = $this->curlOptions;

        $curlOptions[CURLOPT_HEADERFUNCTION] = '*omitted';

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
                'opts' => $curlOptions,
            ],
        ];
    }

    /**
     * Reset curl options
     *
     * @param array $curlOptions
     */
    public function setCurlOptions($curlOptions)
    {
        foreach ($curlOptions as $opt => $value) {
            $this->curlOptions[$opt] = $value;
        }
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

    /**
     * Fetch server response headers
     *
     * @param mixed $curl
     * @param string $header
     *
     * @return int
     */
    protected function fetchResponseHeader($curl, $header)
    {
        $pos = strpos($header, ':');

        if (!empty($pos)) {
            $key = str_replace('-', '_', strtolower(substr($header, 0, $pos)));

            $value = trim(substr($header, $pos + 2));

            $this->responseHeader[$key] = $value;
        }

        return strlen($header);
    }

    /**
     * Convert request headers to the expect curl format
     *
     * @return array
     */
    protected function prepareRequestHeaders()
    {
        $headers = [];

        foreach ($this->requestHeader as $header => $value) {
            $headers[] = trim($header) . ': ' . trim($value);
        }

        return $headers;
    }
}
