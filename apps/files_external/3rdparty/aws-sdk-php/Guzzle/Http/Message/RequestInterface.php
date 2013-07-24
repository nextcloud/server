<?php

namespace Guzzle\Http\Message;

use Guzzle\Common\Collection;
use Guzzle\Common\HasDispatcherInterface;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\Url;
use Guzzle\Http\QueryString;

/**
 * Generic HTTP request interface
 */
interface RequestInterface extends MessageInterface, HasDispatcherInterface
{
    const STATE_NEW = 'new';
    const STATE_COMPLETE = 'complete';
    const STATE_TRANSFER = 'transfer';
    const STATE_ERROR = 'error';

    const GET = 'GET';
    const PUT = 'PUT';
    const POST = 'POST';
    const DELETE = 'DELETE';
    const HEAD = 'HEAD';
    const CONNECT = 'CONNECT';
    const OPTIONS = 'OPTIONS';
    const TRACE = 'TRACE';
    const PATCH = 'PATCH';

    /**
     * @return string
     */
    public function __toString();

    /**
     * Send the request
     *
     * @return Response
     * @throws RequestException on a request error
     */
    public function send();

    /**
     * Set the client used to transport the request
     *
     * @param ClientInterface $client
     *
     * @return self
     */
    public function setClient(ClientInterface $client);

    /**
     * Get the client used to transport the request
     *
     * @return ClientInterface $client
     */
    public function getClient();

    /**
     * Set the URL of the request
     *
     * @param string $url|Url Full URL to set including query string
     *
     * @return self
     */
    public function setUrl($url);

    /**
     * Get the full URL of the request (e.g. 'http://www.guzzle-project.com/')
     *
     * @param bool $asObject Set to TRUE to retrieve the URL as a clone of the URL object owned by the request.
     *
     * @return string|Url
     */
    public function getUrl($asObject = false);

    /**
     * Get the resource part of the the request, including the path, query string, and fragment
     *
     * @return string
     */
    public function getResource();

    /**
     * Get the collection of key value pairs that will be used as the query string in the request
     *
     * @return QueryString
     */
    public function getQuery();

    /**
     * Get the HTTP method of the request
     *
     * @return string
     */
    public function getMethod();

    /**
     * Get the URI scheme of the request (http, https, ftp, etc)
     *
     * @return string
     */
    public function getScheme();

    /**
     * Set the URI scheme of the request (http, https, ftp, etc)
     *
     * @param string $scheme Scheme to set
     *
     * @return self
     */
    public function setScheme($scheme);

    /**
     * Get the host of the request
     *
     * @return string
     */
    public function getHost();

    /**
     * Set the host of the request. Including a port in the host will modify the port of the request.
     *
     * @param string $host Host to set (e.g. www.yahoo.com, www.yahoo.com:80)
     *
     * @return self
     */
    public function setHost($host);

    /**
     * Get the path of the request (e.g. '/', '/index.html')
     *
     * @return string
     */
    public function getPath();

    /**
     * Set the path of the request (e.g. '/', '/index.html')
     *
     * @param string|array $path Path to set or array of segments to implode
     *
     * @return self
     */
    public function setPath($path);

    /**
     * Get the port that the request will be sent on if it has been set
     *
     * @return int|null
     */
    public function getPort();

    /**
     * Set the port that the request will be sent on
     *
     * @param int $port Port number to set
     *
     * @return self
     */
    public function setPort($port);

    /**
     * Get the username to pass in the URL if set
     *
     * @return string|null
     */
    public function getUsername();

    /**
     * Get the password to pass in the URL if set
     *
     * @return string|null
     */
    public function getPassword();

    /**
     * Set HTTP authorization parameters
     *
     * @param string|bool $user     User name or false disable authentication
     * @param string      $password Password
     * @param string      $scheme   Authentication scheme ('Basic', 'Digest', or a CURLAUTH_* constant (deprecated))
     *
     * @return self
     * @link http://www.ietf.org/rfc/rfc2617.txt
     * @link http://php.net/manual/en/function.curl-setopt.php See the available options for CURLOPT_HTTPAUTH
     * @throws RequestException
     */
    public function setAuth($user, $password = '', $scheme = 'Basic');

    /**
     * Get the HTTP protocol version of the request
     *
     * @return string
     */
    public function getProtocolVersion();

    /**
     * Set the HTTP protocol version of the request (e.g. 1.1 or 1.0)
     *
     * @param string $protocol HTTP protocol version to use with the request
     *
     * @return self
     */
    public function setProtocolVersion($protocol);

    /**
     * Get the previously received {@see Response} or NULL if the request has not been sent
     *
     * @return Response|null
     */
    public function getResponse();

    /**
     * Manually set a response for the request.
     *
     * This method is useful for specifying a mock response for the request or setting the response using a cache.
     * Manually setting a response will bypass the actual sending of a request.
     *
     * @param Response $response Response object to set
     * @param bool     $queued   Set to TRUE to keep the request in a state of not having been sent, but queue the
     *                           response for send()
     *
     * @return self Returns a reference to the object.
     */
    public function setResponse(Response $response, $queued = false);

    /**
     * The start of a response has been received for a request and the request is still in progress
     *
     * @param Response $response Response that has been received so far
     *
     * @return self
     */
    public function startResponse(Response $response);

    /**
     * Set the EntityBody that will hold a successful response message's entity body.
     *
     * This method should be invoked when you need to send the response's entity body somewhere other than the normal
     * php://temp buffer. For example, you can send the entity body to a socket, file, or some other custom stream.
     *
     * @param EntityBodyInterface|string|resource $body Response body object. Pass a string to attempt to store the
     *                                                  response body in a local file.
     * @return Request
     */
    public function setResponseBody($body);

    /**
     * Get the EntityBody that will hold the resulting response message's entity body. This response body will only
     * be used for successful responses. Intermediate responses (e.g. redirects) will not use the targeted response
     * body.
     *
     * @return EntityBodyInterface
     */
    public function getResponseBody();

    /**
     * Get the state of the request. One of 'complete', 'transfer', 'new', 'error'
     *
     * @return string
     */
    public function getState();

    /**
     * Set the state of the request
     *
     * @param string $state   State of the request ('complete', 'transfer', 'new', 'error')
     * @param array  $context Contextual information about the state change
     *
     * @return string Returns the current state of the request (which may have changed due to events being fired)
     */
    public function setState($state, array $context = array());

    /**
     * Get the cURL options that will be applied when the cURL handle is created
     *
     * @return Collection
     */
    public function getCurlOptions();

    /**
     * Get an array of Cookies
     *
     * @return array
     */
    public function getCookies();

    /**
     * Get a cookie value by name
     *
     * @param string $name Cookie to retrieve
     *
     * @return null|string
     */
    public function getCookie($name);

    /**
     * Add a Cookie value by name to the Cookie header
     *
     * @param string $name  Name of the cookie to add
     * @param string $value Value to set
     *
     * @return self
     */
    public function addCookie($name, $value);

    /**
     * Remove a specific cookie value by name
     *
     * @param string $name Cookie to remove by name
     *
     * @return self
     */
    public function removeCookie($name);
}
