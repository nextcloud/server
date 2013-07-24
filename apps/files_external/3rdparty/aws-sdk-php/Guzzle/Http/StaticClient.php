<?php

namespace Guzzle\Http;

use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Stream\StreamRequestFactoryInterface;
use Guzzle\Stream\PhpStreamRequestFactory;

/**
 * Simplified interface to Guzzle that does not require a class to be instantiated
 */
final class StaticClient
{
    /** @var Client Guzzle client */
    private static $client;

    /**
     * Mount the client to a simpler class name for a specific client
     *
     * @param string          $className Class name to use to mount
     * @param ClientInterface $client    Client used to send requests
     */
    public static function mount($className = 'Guzzle', ClientInterface $client = null)
    {
        class_alias(__CLASS__, $className);
        if ($client) {
            self::$client = $client;
        }
    }

    /**
     * @param  string $method  HTTP request method (GET, POST, HEAD, DELETE, PUT, etc)
     * @param  string $url     URL of the request
     * @param  array  $options Options to use with the request. See: Guzzle\Http\Message\RequestFactory::applyOptions()
     * @return \Guzzle\Http\Message\Response|\Guzzle\Stream\Stream
     */
    public static function request($method, $url, $options = array())
    {
        // @codeCoverageIgnoreStart
        if (!self::$client) {
            self::$client = new Client();
        }
        // @codeCoverageIgnoreEnd

        $request = self::$client->createRequest($method, $url, null, null, $options);

        if (isset($options['stream'])) {
            if ($options['stream'] instanceof StreamRequestFactoryInterface) {
                return $options['stream']->fromRequest($request);
            } elseif ($options['stream'] == true) {
                $streamFactory = new PhpStreamRequestFactory();
                return $streamFactory->fromRequest($request);
            }
        }

        return $request->send();
    }

    /**
     * Send a GET request
     *
     * @param string $url     URL of the request
     * @param array  $options Array of request options
     *
     * @return \Guzzle\Http\Message\Response
     * @see Guzzle::request for a list of available options
     */
    public static function get($url, $options = array())
    {
        return self::request('GET', $url, $options);
    }

    /**
     * Send a HEAD request
     *
     * @param string $url     URL of the request
     * @param array  $options Array of request options
     *
     * @return \Guzzle\Http\Message\Response
     * @see Guzzle::request for a list of available options
     */
    public static function head($url, $options = array())
    {
        return self::request('HEAD', $url, $options);
    }

    /**
     * Send a DELETE request
     *
     * @param string $url     URL of the request
     * @param array  $options Array of request options
     *
     * @return \Guzzle\Http\Message\Response
     * @see Guzzle::request for a list of available options
     */
    public static function delete($url, $options = array())
    {
        return self::request('DELETE', $url, $options);
    }

    /**
     * Send a POST request
     *
     * @param string $url     URL of the request
     * @param array  $options Array of request options
     *
     * @return \Guzzle\Http\Message\Response
     * @see Guzzle::request for a list of available options
     */
    public static function post($url, $options = array())
    {
        return self::request('POST', $url, $options);
    }

    /**
     * Send a PUT request
     *
     * @param string $url     URL of the request
     * @param array  $options Array of request options
     *
     * @return \Guzzle\Http\Message\Response
     * @see Guzzle::request for a list of available options
     */
    public static function put($url, $options = array())
    {
        return self::request('PUT', $url, $options);
    }

    /**
     * Send a PATCH request
     *
     * @param string $url     URL of the request
     * @param array  $options Array of request options
     *
     * @return \Guzzle\Http\Message\Response
     * @see Guzzle::request for a list of available options
     */
    public static function patch($url, $options = array())
    {
        return self::request('PATCH', $url, $options);
    }

    /**
     * Send an OPTIONS request
     *
     * @param string $url     URL of the request
     * @param array  $options Array of request options
     *
     * @return \Guzzle\Http\Message\Response
     * @see Guzzle::request for a list of available options
     */
    public static function options($url, $options = array())
    {
        return self::request('OPTIONS', $url, $options);
    }
}
