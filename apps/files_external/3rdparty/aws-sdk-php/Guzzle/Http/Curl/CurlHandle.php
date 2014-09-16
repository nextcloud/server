<?php

namespace Guzzle\Http\Curl;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Common\Collection;
use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Parser\ParserRegistry;
use Guzzle\Http\Url;

/**
 * Immutable wrapper for a cURL handle
 */
class CurlHandle
{
    const BODY_AS_STRING = 'body_as_string';
    const PROGRESS = 'progress';
    const DEBUG = 'debug';

    /** @var Collection Curl options */
    protected $options;

    /** @var resource Curl resource handle */
    protected $handle;

    /** @var int CURLE_* error */
    protected $errorNo = CURLE_OK;

    /**
     * Factory method to create a new curl handle based on an HTTP request.
     *
     * There are some helpful options you can set to enable specific behavior:
     * - debug:    Set to true to enable cURL debug functionality to track the actual headers sent over the wire.
     * - progress: Set to true to enable progress function callbacks.
     *
     * @param RequestInterface $request Request
     *
     * @return CurlHandle
     * @throws RuntimeException
     */
    public static function factory(RequestInterface $request)
    {
        $requestCurlOptions = $request->getCurlOptions();
        $mediator = new RequestMediator($request, $requestCurlOptions->get('emit_io'));
        $tempContentLength = null;
        $method = $request->getMethod();
        $bodyAsString = $requestCurlOptions->get(self::BODY_AS_STRING);

        // Prepare url
        $url = (string)$request->getUrl();
        if(($pos = strpos($url, '#')) !== false ){
            // strip fragment from url
            $url = substr($url, 0, $pos);
        }

        // Array of default cURL options.
        $curlOptions = array(
            CURLOPT_URL            => $url,
            CURLOPT_CONNECTTIMEOUT => 150,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER         => false,
            CURLOPT_PORT           => $request->getPort(),
            CURLOPT_HTTPHEADER     => array(),
            CURLOPT_WRITEFUNCTION  => array($mediator, 'writeResponseBody'),
            CURLOPT_HEADERFUNCTION => array($mediator, 'receiveResponseHeader'),
            CURLOPT_HTTP_VERSION   => $request->getProtocolVersion() === '1.0'
                ? CURL_HTTP_VERSION_1_0 : CURL_HTTP_VERSION_1_1,
            // Verifies the authenticity of the peer's certificate
            CURLOPT_SSL_VERIFYPEER => 1,
            // Certificate must indicate that the server is the server to which you meant to connect
            CURLOPT_SSL_VERIFYHOST => 2
        );

        if (defined('CURLOPT_PROTOCOLS')) {
            // Allow only HTTP and HTTPS protocols
            $curlOptions[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        // Add CURLOPT_ENCODING if Accept-Encoding header is provided
        if ($acceptEncodingHeader = $request->getHeader('Accept-Encoding')) {
            $curlOptions[CURLOPT_ENCODING] = (string) $acceptEncodingHeader;
            // Let cURL set the Accept-Encoding header, prevents duplicate values
            $request->removeHeader('Accept-Encoding');
        }

        // Enable curl debug information if the 'debug' param was set
        if ($requestCurlOptions->get('debug')) {
            $curlOptions[CURLOPT_STDERR] = fopen('php://temp', 'r+');
            // @codeCoverageIgnoreStart
            if (false === $curlOptions[CURLOPT_STDERR]) {
                throw new RuntimeException('Unable to create a stream for CURLOPT_STDERR');
            }
            // @codeCoverageIgnoreEnd
            $curlOptions[CURLOPT_VERBOSE] = true;
        }

        // Specify settings according to the HTTP method
        if ($method == 'GET') {
            $curlOptions[CURLOPT_HTTPGET] = true;
        } elseif ($method == 'HEAD') {
            $curlOptions[CURLOPT_NOBODY] = true;
            // HEAD requests do not use a write function
            unset($curlOptions[CURLOPT_WRITEFUNCTION]);
        } elseif (!($request instanceof EntityEnclosingRequest)) {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
        } else {

            $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;

            // Handle sending raw bodies in a request
            if ($request->getBody()) {
                // You can send the body as a string using curl's CURLOPT_POSTFIELDS
                if ($bodyAsString) {
                    $curlOptions[CURLOPT_POSTFIELDS] = (string) $request->getBody();
                    // Allow curl to add the Content-Length for us to account for the times when
                    // POST redirects are followed by GET requests
                    if ($tempContentLength = $request->getHeader('Content-Length')) {
                        $tempContentLength = (int) (string) $tempContentLength;
                    }
                    // Remove the curl generated Content-Type header if none was set manually
                    if (!$request->hasHeader('Content-Type')) {
                        $curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Type:';
                    }
                } else {
                    $curlOptions[CURLOPT_UPLOAD] = true;
                    // Let cURL handle setting the Content-Length header
                    if ($tempContentLength = $request->getHeader('Content-Length')) {
                        $tempContentLength = (int) (string) $tempContentLength;
                        $curlOptions[CURLOPT_INFILESIZE] = $tempContentLength;
                    }
                    // Add a callback for curl to read data to send with the request only if a body was specified
                    $curlOptions[CURLOPT_READFUNCTION] = array($mediator, 'readRequestBody');
                    // Attempt to seek to the start of the stream
                    $request->getBody()->seek(0);
                }

            } else {

                // Special handling for POST specific fields and files
                $postFields = false;
                if (count($request->getPostFiles())) {
                    $postFields = $request->getPostFields()->useUrlEncoding(false)->urlEncode();
                    foreach ($request->getPostFiles() as $key => $data) {
                        $prefixKeys = count($data) > 1;
                        foreach ($data as $index => $file) {
                            // Allow multiple files in the same key
                            $fieldKey = $prefixKeys ? "{$key}[{$index}]" : $key;
                            $postFields[$fieldKey] = $file->getCurlValue();
                        }
                    }
                } elseif (count($request->getPostFields())) {
                    $postFields = (string) $request->getPostFields()->useUrlEncoding(true);
                }

                if ($postFields !== false) {
                    if ($method == 'POST') {
                        unset($curlOptions[CURLOPT_CUSTOMREQUEST]);
                        $curlOptions[CURLOPT_POST] = true;
                    }
                    $curlOptions[CURLOPT_POSTFIELDS] = $postFields;
                    $request->removeHeader('Content-Length');
                }
            }

            // If the Expect header is not present, prevent curl from adding it
            if (!$request->hasHeader('Expect')) {
                $curlOptions[CURLOPT_HTTPHEADER][] = 'Expect:';
            }
        }

        // If a Content-Length header was specified but we want to allow curl to set one for us
        if (null !== $tempContentLength) {
            $request->removeHeader('Content-Length');
        }

        // Set custom cURL options
        foreach ($requestCurlOptions->toArray() as $key => $value) {
            if (is_numeric($key)) {
                $curlOptions[$key] = $value;
            }
        }

        // Do not set an Accept header by default
        if (!isset($curlOptions[CURLOPT_ENCODING])) {
            $curlOptions[CURLOPT_HTTPHEADER][] = 'Accept:';
        }

        // Add any custom headers to the request. Empty headers will cause curl to not send the header at all.
        foreach ($request->getHeaderLines() as $line) {
            $curlOptions[CURLOPT_HTTPHEADER][] = $line;
        }

        // Add the content-length header back if it was temporarily removed
        if ($tempContentLength) {
            $request->setHeader('Content-Length', $tempContentLength);
        }

        // Apply the options to a new cURL handle.
        $handle = curl_init();

        // Enable the progress function if the 'progress' param was set
        if ($requestCurlOptions->get('progress')) {
            // Wrap the function in a function that provides the curl handle to the mediator's progress function
            // Using this rather than injecting the handle into the mediator prevents a circular reference
            $curlOptions[CURLOPT_PROGRESSFUNCTION] = function () use ($mediator, $handle) {
                $args = func_get_args();
                $args[] = $handle;

                // PHP 5.5 pushed the handle onto the start of the args
                if (is_resource($args[0])) {
                    array_shift($args);
                }

                call_user_func_array(array($mediator, 'progress'), $args);
            };
            $curlOptions[CURLOPT_NOPROGRESS] = false;
        }

        curl_setopt_array($handle, $curlOptions);

        return new static($handle, $curlOptions);
    }

    /**
     * Construct a new CurlHandle object that wraps a cURL handle
     *
     * @param resource         $handle  Configured cURL handle resource
     * @param Collection|array $options Curl options to use with the handle
     *
     * @throws InvalidArgumentException
     */
    public function __construct($handle, $options)
    {
        if (!is_resource($handle)) {
            throw new InvalidArgumentException('Invalid handle provided');
        }
        if (is_array($options)) {
            $this->options = new Collection($options);
        } elseif ($options instanceof Collection) {
            $this->options = $options;
        } else {
            throw new InvalidArgumentException('Expected array or Collection');
        }
        $this->handle = $handle;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Close the curl handle
     */
    public function close()
    {
        if (is_resource($this->handle)) {
            curl_close($this->handle);
        }
        $this->handle = null;
    }

    /**
     * Check if the handle is available and still OK
     *
     * @return bool
     */
    public function isAvailable()
    {
        return is_resource($this->handle);
    }

    /**
     * Get the last error that occurred on the cURL handle
     *
     * @return string
     */
    public function getError()
    {
        return $this->isAvailable() ? curl_error($this->handle) : '';
    }

    /**
     * Get the last error number that occurred on the cURL handle
     *
     * @return int
     */
    public function getErrorNo()
    {
        if ($this->errorNo) {
            return $this->errorNo;
        }

        return $this->isAvailable() ? curl_errno($this->handle) : CURLE_OK;
    }

    /**
     * Set the curl error number
     *
     * @param int $error Error number to set
     *
     * @return CurlHandle
     */
    public function setErrorNo($error)
    {
        $this->errorNo = $error;

        return $this;
    }

    /**
     * Get cURL curl_getinfo data
     *
     * @param int $option Option to retrieve. Pass null to retrieve all data as an array.
     *
     * @return array|mixed
     */
    public function getInfo($option = null)
    {
        if (!is_resource($this->handle)) {
            return null;
        }

        if (null !== $option) {
            return curl_getinfo($this->handle, $option) ?: null;
        }

        return curl_getinfo($this->handle) ?: array();
    }

    /**
     * Get the stderr output
     *
     * @param bool $asResource Set to TRUE to get an fopen resource
     *
     * @return string|resource|null
     */
    public function getStderr($asResource = false)
    {
        $stderr = $this->getOptions()->get(CURLOPT_STDERR);
        if (!$stderr) {
            return null;
        }

        if ($asResource) {
            return $stderr;
        }

        fseek($stderr, 0);
        $e = stream_get_contents($stderr);
        fseek($stderr, 0, SEEK_END);

        return $e;
    }

    /**
     * Get the URL that this handle is connecting to
     *
     * @return Url
     */
    public function getUrl()
    {
        return Url::factory($this->options->get(CURLOPT_URL));
    }

    /**
     * Get the wrapped curl handle
     *
     * @return resource|null Returns the cURL handle or null if it was closed
     */
    public function getHandle()
    {
        return $this->isAvailable() ? $this->handle : null;
    }

    /**
     * Get the cURL setopt options of the handle. Changing values in the return object will have no effect on the curl
     * handle after it is created.
     *
     * @return Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Update a request based on the log messages of the CurlHandle
     *
     * @param RequestInterface $request Request to update
     */
    public function updateRequestFromTransfer(RequestInterface $request)
    {
        if (!$request->getResponse()) {
            return;
        }

        // Update the transfer stats of the response
        $request->getResponse()->setInfo($this->getInfo());

        if (!$log = $this->getStderr(true)) {
            return;
        }

        // Parse the cURL stderr output for outgoing requests
        $headers = '';
        fseek($log, 0);
        while (($line = fgets($log)) !== false) {
            if ($line && $line[0] == '>') {
                $headers = substr(trim($line), 2) . "\r\n";
                while (($line = fgets($log)) !== false) {
                    if ($line[0] == '*' || $line[0] == '<') {
                        break;
                    } else {
                        $headers .= trim($line) . "\r\n";
                    }
                }
            }
        }

        // Add request headers to the request exactly as they were sent
        if ($headers) {
            $parsed = ParserRegistry::getInstance()->getParser('message')->parseRequest($headers);
            if (!empty($parsed['headers'])) {
                $request->setHeaders(array());
                foreach ($parsed['headers'] as $name => $value) {
                    $request->setHeader($name, $value);
                }
            }
            if (!empty($parsed['version'])) {
                $request->setProtocolVersion($parsed['version']);
            }
        }
    }

    /**
     * Parse the config and replace curl.* configurators into the constant based values so it can be used elsewhere
     *
     * @param array|Collection $config The configuration we want to parse
     *
     * @return array
     */
    public static function parseCurlConfig($config)
    {
        $curlOptions = array();
        foreach ($config as $key => $value) {
            if (is_string($key) && defined($key)) {
                // Convert constants represented as string to constant int values
                $key = constant($key);
            }
            if (is_string($value) && defined($value)) {
                $value = constant($value);
            }
            $curlOptions[$key] = $value;
        }

        return $curlOptions;
    }
}
