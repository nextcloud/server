<?php

namespace Guzzle\Http\Message;

use Guzzle\Common\Collection;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\Url;

/**
 * Request factory used to create HTTP requests
 */
interface RequestFactoryInterface
{
    const OPTIONS_NONE = 0;
    const OPTIONS_AS_DEFAULTS = 1;

    /**
     * Create a new request based on an HTTP message
     *
     * @param string $message HTTP message as a string
     *
     * @return RequestInterface
     */
    public function fromMessage($message);

    /**
     * Create a request from URL parts as returned from parse_url()
     *
     * @param string $method HTTP method (GET, POST, PUT, HEAD, DELETE, etc)
     *
     * @param array $urlParts URL parts containing the same keys as parse_url()
     *     - scheme: e.g. http
     *     - host:   e.g. www.guzzle-project.com
     *     - port:   e.g. 80
     *     - user:   e.g. michael
     *     - pass:   e.g. rocks
     *     - path:   e.g. / OR /index.html
     *     - query:  after the question mark ?
     * @param array|Collection                          $headers         HTTP headers
     * @param string|resource|array|EntityBodyInterface $body            Body to send in the request
     * @param string                                    $protocol        Protocol (HTTP, SPYDY, etc)
     * @param string                                    $protocolVersion 1.0, 1.1, etc
     *
     * @return RequestInterface
     */
    public function fromParts(
        $method,
        array $urlParts,
        $headers = null,
        $body = null,
        $protocol = 'HTTP',
        $protocolVersion = '1.1'
    );

    /**
     * Create a new request based on the HTTP method
     *
     * @param string                                    $method  HTTP method (GET, POST, PUT, PATCH, HEAD, DELETE, ...)
     * @param string|Url                                $url     HTTP URL to connect to
     * @param array|Collection                          $headers HTTP headers
     * @param string|resource|array|EntityBodyInterface $body    Body to send in the request
     * @param array                                     $options Array of options to apply to the request
     *
     * @return RequestInterface
     */
    public function create($method, $url, $headers = null, $body = null, array $options = array());

    /**
     * Apply an associative array of options to the request
     *
     * @param RequestInterface $request Request to update
     * @param array            $options Options to use with the request. Available options are:
     *        "headers": Associative array of headers
     *        "query": Associative array of query string values to add to the request
     *        "body": Body of a request, including an EntityBody, string, or array when sending POST requests.
     *        "auth": Array of HTTP authentication parameters to use with the request. The array must contain the
     *            username in index [0], the password in index [2], and can optionally contain the authentication type
     *            in index [3]. The authentication types are: "Basic", "Digest", "NTLM", "Any" (defaults to "Basic").
     *        "cookies": Associative array of cookies
     *        "allow_redirects": Set to false to disable redirects
     *        "save_to": String, fopen resource, or EntityBody object used to store the body of the response
     *        "events": Associative array mapping event names to a closure or array of (priority, closure)
     *        "plugins": Array of plugins to add to the request
     *        "exceptions": Set to false to disable throwing exceptions on an HTTP level error (e.g. 404, 500, etc)
     *        "params": Set custom request data parameters on a request. (Note: these are not query string parameters)
     *        "timeout": Float describing the timeout of the request in seconds
     *        "connect_timeout": Float describing the number of seconds to wait while trying to connect. Use 0 to wait
     *            indefinitely.
     *        "verify": Set to true to enable SSL cert validation (the default), false to disable, or supply the path
     *            to a CA bundle to enable verification using a custom certificate.
     *        "cert": Set to a string to specify the path to a file containing a PEM formatted certificate. If a
     *            password is required, then set an array containing the path to the PEM file followed by the the
     *            password required for the certificate.
     *        "ssl_key": Specify the path to a file containing a private SSL key in PEM format. If a password is
     *            required, then set an array containing the path to the SSL key followed by the password required for
     *            the certificate.
     *        "proxy": Specify an HTTP proxy (e.g. "http://username:password@192.168.16.1:10")
     *        "debug": Set to true to display all data sent over the wire
     * @param int $flags Bitwise flags to apply when applying the options to the request. Defaults to no special
     *                   options. `1` (OPTIONS_AS_DEFAULTS): When specified, options will only update a request when
     *                   the value does not already exist on the request. This is only supported by "query" and
     *                   "headers". Other bitwise options may be added in the future.
     */
    public function applyOptions(RequestInterface $request, array $options = array(), $flags = self::OPTIONS_NONE);
}
