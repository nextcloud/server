<?php

namespace Guzzle\Stream;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Url;

/**
 * Factory used to create fopen streams using PHP's http and https stream wrappers
 *
 * Note: PHP's http stream wrapper only supports streaming downloads. It does not support streaming uploads.
 */
class PhpStreamRequestFactory implements StreamRequestFactoryInterface
{
    /** @var resource Stream context options */
    protected $context;

    /** @var array Stream context */
    protected $contextOptions;

    /** @var Url Stream URL */
    protected $url;

    /** @var array Last response headers received by the HTTP request */
    protected $lastResponseHeaders;

    /**
     * {@inheritdoc}
     *
     * The $params array can contain the following custom keys specific to the PhpStreamRequestFactory:
     * - stream_class: The name of a class to create instead of a Guzzle\Stream\Stream object
     */
    public function fromRequest(RequestInterface $request, $context = array(), array $params = array())
    {
        if (is_resource($context)) {
            $this->contextOptions = stream_context_get_options($context);
            $this->context = $context;
        } elseif (is_array($context) || !$context) {
            $this->contextOptions = $context;
            $this->createContext($params);
        } elseif ($context) {
            throw new InvalidArgumentException('$context must be an array or resource');
        }

        // Dispatch the before send event
        $request->dispatch('request.before_send', array(
            'request'         => $request,
            'context'         => $this->context,
            'context_options' => $this->contextOptions
        ));

        $this->setUrl($request);
        $this->addDefaultContextOptions($request);
        $this->addSslOptions($request);
        $this->addBodyOptions($request);
        $this->addProxyOptions($request);

        // Create the file handle but silence errors
        return $this->createStream($params)
            ->setCustomData('request', $request)
            ->setCustomData('response_headers', $this->getLastResponseHeaders());
    }

    /**
     * Set an option on the context and the internal options array
     *
     * @param string $wrapper   Stream wrapper name of http
     * @param string $name      Context name
     * @param mixed  $value     Context value
     * @param bool   $overwrite Set to true to overwrite an existing value
     */
    protected function setContextValue($wrapper, $name, $value, $overwrite = false)
    {
        if (!isset($this->contextOptions[$wrapper])) {
            $this->contextOptions[$wrapper] = array($name => $value);
        } elseif (!$overwrite && isset($this->contextOptions[$wrapper][$name])) {
            return;
        }
        $this->contextOptions[$wrapper][$name] = $value;
        stream_context_set_option($this->context, $wrapper, $name, $value);
    }

    /**
     * Create a stream context
     *
     * @param array $params Parameter array
     */
    protected function createContext(array $params)
    {
        $options = $this->contextOptions;
        $this->context = $this->createResource(function () use ($params, $options) {
            return stream_context_create($options, $params);
        });
    }

    /**
     * Get the last response headers received by the HTTP request
     *
     * @return array
     */
    public function getLastResponseHeaders()
    {
        return $this->lastResponseHeaders;
    }

    /**
     * Adds the default context options to the stream context options
     *
     * @param RequestInterface $request Request
     */
    protected function addDefaultContextOptions(RequestInterface $request)
    {
        $this->setContextValue('http', 'method', $request->getMethod());
        $headers = $request->getHeaderLines();

        // "Connection: close" is required to get streams to work in HTTP 1.1
        if (!$request->hasHeader('Connection')) {
            $headers[] = 'Connection: close';
        }

        $this->setContextValue('http', 'header', $headers);
        $this->setContextValue('http', 'protocol_version', $request->getProtocolVersion());
        $this->setContextValue('http', 'ignore_errors', true);
    }

    /**
     * Set the URL to use with the factory
     *
     * @param RequestInterface $request Request that owns the URL
     */
    protected function setUrl(RequestInterface $request)
    {
        $this->url = $request->getUrl(true);

        // Check for basic Auth username
        if ($request->getUsername()) {
            $this->url->setUsername($request->getUsername());
        }

        // Check for basic Auth password
        if ($request->getPassword()) {
            $this->url->setPassword($request->getPassword());
        }
    }

    /**
     * Add SSL options to the stream context
     *
     * @param RequestInterface $request Request
     */
    protected function addSslOptions(RequestInterface $request)
    {
        if ($request->getCurlOptions()->get(CURLOPT_SSL_VERIFYPEER)) {
            $this->setContextValue('ssl', 'verify_peer', true, true);
            if ($cafile = $request->getCurlOptions()->get(CURLOPT_CAINFO)) {
                $this->setContextValue('ssl', 'cafile', $cafile, true);
            }
        } else {
            $this->setContextValue('ssl', 'verify_peer', false, true);
        }
    }

    /**
     * Add body (content) specific options to the context options
     *
     * @param RequestInterface $request
     */
    protected function addBodyOptions(RequestInterface $request)
    {
        // Add the content for the request if needed
        if (!($request instanceof EntityEnclosingRequestInterface)) {
            return;
        }

        if (count($request->getPostFields())) {
            $this->setContextValue('http', 'content', (string) $request->getPostFields(), true);
        } elseif ($request->getBody()) {
            $this->setContextValue('http', 'content', (string) $request->getBody(), true);
        }

        // Always ensure a content-length header is sent
        if (isset($this->contextOptions['http']['content'])) {
            $headers = isset($this->contextOptions['http']['header']) ? $this->contextOptions['http']['header'] : array();
            $headers[] = 'Content-Length: ' . strlen($this->contextOptions['http']['content']);
            $this->setContextValue('http', 'header', $headers, true);
        }
    }

    /**
     * Add proxy parameters to the context if needed
     *
     * @param RequestInterface $request Request
     */
    protected function addProxyOptions(RequestInterface $request)
    {
        if ($proxy = $request->getCurlOptions()->get(CURLOPT_PROXY)) {
            $this->setContextValue('http', 'proxy', $proxy);
        }
    }

    /**
     * Create the stream for the request with the context options
     *
     * @param array $params Parameters of the stream
     *
     * @return StreamInterface
     */
    protected function createStream(array $params)
    {
        $http_response_header = null;
        $url = $this->url;
        $context = $this->context;
        $fp = $this->createResource(function () use ($context, $url, &$http_response_header) {
            return fopen((string) $url, 'r', false, $context);
        });

        // Determine the class to instantiate
        $className = isset($params['stream_class']) ? $params['stream_class'] : __NAMESPACE__ . '\\Stream';

        /** @var $stream StreamInterface */
        $stream = new $className($fp);

        // Track the response headers of the request
        if (isset($http_response_header)) {
            $this->lastResponseHeaders = $http_response_header;
            $this->processResponseHeaders($stream);
        }

        return $stream;
    }

    /**
     * Process response headers
     *
     * @param StreamInterface $stream
     */
    protected function processResponseHeaders(StreamInterface $stream)
    {
        // Set the size on the stream if it was returned in the response
        foreach ($this->lastResponseHeaders as $header) {
            if ((stripos($header, 'Content-Length:')) === 0) {
                $stream->setSize(trim(substr($header, 15)));
            }
        }
    }

    /**
     * Create a resource and check to ensure it was created successfully
     *
     * @param callable $callback Closure to invoke that must return a valid resource
     *
     * @return resource
     * @throws RuntimeException on error
     */
    protected function createResource($callback)
    {
        // Turn off error reporting while we try to initiate the request
        $level = error_reporting(0);
        $resource = call_user_func($callback);
        error_reporting($level);

        // If the resource could not be created, then grab the last error and throw an exception
        if (false === $resource) {
            $message = 'Error creating resource. ';
            foreach (error_get_last() as $key => $value) {
                $message .= "[{$key}] {$value} ";
            }
            throw new RuntimeException(trim($message));
        }

        return $resource;
    }
}
