<?php

namespace Guzzle\Http;

use Guzzle\Common\HasDispatcherInterface;
use Guzzle\Common\Collection;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\RequestInterface;

/**
 * Client interface for send HTTP requests
 */
interface ClientInterface extends HasDispatcherInterface
{
    const CREATE_REQUEST = 'client.create_request';

    /** @var string RFC 1123 HTTP-Date */
    const HTTP_DATE = 'D, d M Y H:i:s \G\M\T';

    /**
     * Set the configuration object to use with the client
     *
     * @param array|Collection $config Parameters that define how the client behaves
     *
     * @return self
     */
    public function setConfig($config);

    /**
     * Get a configuration setting or all of the configuration settings. The Collection result of this method can be
     * modified to change the configuration settings of a client.
     *
     * A client should honor the following special values:
     *
     * - request.options: Associative array of default RequestFactory options to apply to each request
     * - request.params: Associative array of request parameters (data values) to apply to each request
     * - curl.options: Associative array of cURL configuration settings to apply to each request
     * - ssl.certificate_authority: Path a CAINFO, CAPATH, true to use strict defaults, or false to disable verification
     * - redirect.disable: Set to true to disable redirects
     *
     * @param bool|string $key Configuration value to retrieve. Set to FALSE to retrieve all values of the client.
     *                         The object return can be modified, and modifications will affect the client's config.
     * @return mixed|Collection
     * @see \Guzzle\Http\Message\RequestFactoryInterface::applyOptions for a full list of request.options options
     */
    public function getConfig($key = false);

    /**
     * Create and return a new {@see RequestInterface} configured for the client.
     *
     * Use an absolute path to override the base path of the client, or a relative path to append to the base path of
     * the client. The URI can contain the query string as well. Use an array to provide a URI template and additional
     * variables to use in the URI template expansion.
     *
     * @param string                                    $method  HTTP method. Defaults to GET
     * @param string|array                              $uri     Resource URI.
     * @param array|Collection                          $headers HTTP headers
     * @param string|resource|array|EntityBodyInterface $body    Entity body of request (POST/PUT) or response (GET)
     * @param array                                     $options Array of options to apply to the request
     *
     * @return RequestInterface
     * @throws InvalidArgumentException if a URI array is passed that does not contain exactly two elements: the URI
     *                                  followed by template variables
     */
    public function createRequest(
        $method = RequestInterface::GET,
        $uri = null,
        $headers = null,
        $body = null,
        array $options = array()
    );

    /**
     * Create a GET request for the client
     *
     * @param string|array     $uri     Resource URI
     * @param array|Collection $headers HTTP headers
     * @param array            $options Options to apply to the request. For BC compatibility, you can also pass a
     *                                  string to tell Guzzle to download the body of the response to a particular
     *                                  location. Use the 'body' option instead for forward compatibility.
     * @return RequestInterface
     * @see    Guzzle\Http\ClientInterface::createRequest()
     */
    public function get($uri = null, $headers = null, $options = array());

    /**
     * Create a HEAD request for the client
     *
     * @param string|array     $uri     Resource URI
     * @param array|Collection $headers HTTP headers
     * @param array            $options Options to apply to the request
     *
     * @return RequestInterface
     * @see    Guzzle\Http\ClientInterface::createRequest()
     */
    public function head($uri = null, $headers = null, array $options = array());

    /**
     * Create a DELETE request for the client
     *
     * @param string|array                        $uri     Resource URI
     * @param array|Collection                    $headers HTTP headers
     * @param string|resource|EntityBodyInterface $body    Body to send in the request
     * @param array                               $options Options to apply to the request
     *
     * @return EntityEnclosingRequestInterface
     * @see    Guzzle\Http\ClientInterface::createRequest()
     */
    public function delete($uri = null, $headers = null, $body = null, array $options = array());

    /**
     * Create a PUT request for the client
     *
     * @param string|array                        $uri     Resource URI
     * @param array|Collection                    $headers HTTP headers
     * @param string|resource|EntityBodyInterface $body    Body to send in the request
     * @param array                               $options Options to apply to the request
     *
     * @return EntityEnclosingRequestInterface
     * @see    Guzzle\Http\ClientInterface::createRequest()
     */
    public function put($uri = null, $headers = null, $body = null, array $options = array());

    /**
     * Create a PATCH request for the client
     *
     * @param string|array                        $uri     Resource URI
     * @param array|Collection                    $headers HTTP headers
     * @param string|resource|EntityBodyInterface $body    Body to send in the request
     * @param array                               $options Options to apply to the request
     *
     * @return EntityEnclosingRequestInterface
     * @see    Guzzle\Http\ClientInterface::createRequest()
     */
    public function patch($uri = null, $headers = null, $body = null, array $options = array());

    /**
     * Create a POST request for the client
     *
     * @param string|array                                $uri      Resource URI
     * @param array|Collection                            $headers  HTTP headers
     * @param array|Collection|string|EntityBodyInterface $postBody POST body. Can be a string, EntityBody, or
     *                                                    associative array of POST fields to send in the body of the
     *                                                    request. Prefix a value in the array with the @ symbol to
     *                                                    reference a file.
     * @param array                                       $options Options to apply to the request
     *
     * @return EntityEnclosingRequestInterface
     * @see    Guzzle\Http\ClientInterface::createRequest()
     */
    public function post($uri = null, $headers = null, $postBody = null, array $options = array());

    /**
     * Create an OPTIONS request for the client
     *
     * @param string|array $uri     Resource URI
     * @param array        $options Options to apply to the request
     *
     * @return RequestInterface
     * @see    Guzzle\Http\ClientInterface::createRequest()
     */
    public function options($uri = null, array $options = array());

    /**
     * Sends a single request or an array of requests in parallel
     *
     * @param array|RequestInterface $requests One or more RequestInterface objects to send
     *
     * @return \Guzzle\Http\Message\Response|array Returns a single Response or an array of Response objects
     */
    public function send($requests);

    /**
     * Get the client's base URL as either an expanded or raw URI template
     *
     * @param bool $expand Set to FALSE to get the raw base URL without URI template expansion
     *
     * @return string|null
     */
    public function getBaseUrl($expand = true);

    /**
     * Set the base URL of the client
     *
     * @param string $url The base service endpoint URL of the webservice
     *
     * @return self
     */
    public function setBaseUrl($url);

    /**
     * Set the User-Agent header to be used on all requests from the client
     *
     * @param string $userAgent      User agent string
     * @param bool   $includeDefault Set to true to prepend the value to Guzzle's default user agent string
     *
     * @return self
     */
    public function setUserAgent($userAgent, $includeDefault = false);

    /**
     * Set SSL verification options.
     *
     * Setting $certificateAuthority to TRUE will result in the bundled cacert.pem being used to verify against the
     * remote host.
     *
     * Alternate certificates to verify against can be specified with the $certificateAuthority option set to the full
     * path to a certificate file, or the path to a directory containing certificates.
     *
     * Setting $certificateAuthority to FALSE will turn off peer verification, unset the bundled cacert.pem, and
     * disable host verification. Please don't do this unless you really know what you're doing, and why you're doing
     * it.
     *
     * @param string|bool $certificateAuthority bool, file path, or directory path
     * @param bool        $verifyPeer           FALSE to stop from verifying the peer's certificate.
     * @param int         $verifyHost           Set to 1 to check the existence of a common name in the SSL peer
     *                                          certificate. 2 to check the existence of a common name and also verify
     *                                          that it matches the hostname provided.
     * @return self
     */
    public function setSslVerification($certificateAuthority = true, $verifyPeer = true, $verifyHost = 2);
}
