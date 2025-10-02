<?php

namespace GuzzleHttp;

/**
 * This class contains a list of built-in Guzzle request options.
 *
 * @see https://docs.guzzlephp.org/en/latest/request-options.html
 */
final class RequestOptions
{
    /**
     * allow_redirects: (bool|array) Controls redirect behavior. Pass false
     * to disable redirects, pass true to enable redirects, pass an
     * associative to provide custom redirect settings. Defaults to "false".
     * This option only works if your handler has the RedirectMiddleware. When
     * passing an associative array, you can provide the following key value
     * pairs:
     *
     * - max: (int, default=5) maximum number of allowed redirects.
     * - strict: (bool, default=false) Set to true to use strict redirects
     *   meaning redirect POST requests with POST requests vs. doing what most
     *   browsers do which is redirect POST requests with GET requests
     * - referer: (bool, default=false) Set to true to enable the Referer
     *   header.
     * - protocols: (array, default=['http', 'https']) Allowed redirect
     *   protocols.
     * - on_redirect: (callable) PHP callable that is invoked when a redirect
     *   is encountered. The callable is invoked with the request, the redirect
     *   response that was received, and the effective URI. Any return value
     *   from the on_redirect function is ignored.
     */
    public const ALLOW_REDIRECTS = 'allow_redirects';

    /**
     * auth: (array) Pass an array of HTTP authentication parameters to use
     * with the request. The array must contain the username in index [0],
     * the password in index [1], and you can optionally provide a built-in
     * authentication type in index [2]. Pass null to disable authentication
     * for a request.
     */
    public const AUTH = 'auth';

    /**
     * body: (resource|string|null|int|float|StreamInterface|callable|\Iterator)
     * Body to send in the request.
     */
    public const BODY = 'body';

    /**
     * cert: (string|array) Set to a string to specify the path to a file
     * containing a PEM formatted SSL client side certificate. If a password
     * is required, then set cert to an array containing the path to the PEM
     * file in the first array element followed by the certificate password
     * in the second array element.
     */
    public const CERT = 'cert';

    /**
     * cookies: (bool|GuzzleHttp\Cookie\CookieJarInterface, default=false)
     * Specifies whether or not cookies are used in a request or what cookie
     * jar to use or what cookies to send. This option only works if your
     * handler has the `cookie` middleware. Valid values are `false` and
     * an instance of {@see Cookie\CookieJarInterface}.
     */
    public const COOKIES = 'cookies';

    /**
     * connect_timeout: (float, default=0) Float describing the number of
     * seconds to wait while trying to connect to a server. Use 0 to wait
     * 300 seconds (the default behavior).
     */
    public const CONNECT_TIMEOUT = 'connect_timeout';

    /**
     * crypto_method: (int) A value describing the minimum TLS protocol
     * version to use.
     *
     * This setting must be set to one of the
     * ``STREAM_CRYPTO_METHOD_TLS*_CLIENT`` constants. PHP 7.4 or higher is
     * required in order to use TLS 1.3, and cURL 7.34.0 or higher is required
     * in order to specify a crypto method, with cURL 7.52.0 or higher being
     * required to use TLS 1.3.
     */
    public const CRYPTO_METHOD = 'crypto_method';

    /**
     * debug: (bool|resource) Set to true or set to a PHP stream returned by
     * fopen()  enable debug output with the HTTP handler used to send a
     * request.
     */
    public const DEBUG = 'debug';

    /**
     * decode_content: (bool, default=true) Specify whether or not
     * Content-Encoding responses (gzip, deflate, etc.) are automatically
     * decoded.
     */
    public const DECODE_CONTENT = 'decode_content';

    /**
     * delay: (int) The amount of time to delay before sending in milliseconds.
     */
    public const DELAY = 'delay';

    /**
     * expect: (bool|integer) Controls the behavior of the
     * "Expect: 100-Continue" header.
     *
     * Set to `true` to enable the "Expect: 100-Continue" header for all
     * requests that sends a body. Set to `false` to disable the
     * "Expect: 100-Continue" header for all requests. Set to a number so that
     * the size of the payload must be greater than the number in order to send
     * the Expect header. Setting to a number will send the Expect header for
     * all requests in which the size of the payload cannot be determined or
     * where the body is not rewindable.
     *
     * By default, Guzzle will add the "Expect: 100-Continue" header when the
     * size of the body of a request is greater than 1 MB and a request is
     * using HTTP/1.1.
     */
    public const EXPECT = 'expect';

    /**
     * form_params: (array) Associative array of form field names to values
     * where each value is a string or array of strings. Sets the Content-Type
     * header to application/x-www-form-urlencoded when no Content-Type header
     * is already present.
     */
    public const FORM_PARAMS = 'form_params';

    /**
     * headers: (array) Associative array of HTTP headers. Each value MUST be
     * a string or array of strings.
     */
    public const HEADERS = 'headers';

    /**
     * http_errors: (bool, default=true) Set to false to disable exceptions
     * when a non- successful HTTP response is received. By default,
     * exceptions will be thrown for 4xx and 5xx responses. This option only
     * works if your handler has the `httpErrors` middleware.
     */
    public const HTTP_ERRORS = 'http_errors';

    /**
     * idn: (bool|int, default=true) A combination of IDNA_* constants for
     * idn_to_ascii() PHP's function (see "options" parameter). Set to false to
     * disable IDN support completely, or to true to use the default
     * configuration (IDNA_DEFAULT constant).
     */
    public const IDN_CONVERSION = 'idn_conversion';

    /**
     * json: (mixed) Adds JSON data to a request. The provided value is JSON
     * encoded and a Content-Type header of application/json will be added to
     * the request if no Content-Type header is already present.
     */
    public const JSON = 'json';

    /**
     * multipart: (array) Array of associative arrays, each containing a
     * required "name" key mapping to the form field, name, a required
     * "contents" key mapping to a StreamInterface|resource|string, an
     * optional "headers" associative array of custom headers, and an
     * optional "filename" key mapping to a string to send as the filename in
     * the part. If no "filename" key is present, then no "filename" attribute
     * will be added to the part.
     */
    public const MULTIPART = 'multipart';

    /**
     * on_headers: (callable) A callable that is invoked when the HTTP headers
     * of the response have been received but the body has not yet begun to
     * download.
     */
    public const ON_HEADERS = 'on_headers';

    /**
     * on_stats: (callable) allows you to get access to transfer statistics of
     * a request and access the lower level transfer details of the handler
     * associated with your client. ``on_stats`` is a callable that is invoked
     * when a handler has finished sending a request. The callback is invoked
     * with transfer statistics about the request, the response received, or
     * the error encountered. Included in the data is the total amount of time
     * taken to send the request.
     */
    public const ON_STATS = 'on_stats';

    /**
     * progress: (callable) Defines a function to invoke when transfer
     * progress is made. The function accepts the following positional
     * arguments: the total number of bytes expected to be downloaded, the
     * number of bytes downloaded so far, the number of bytes expected to be
     * uploaded, the number of bytes uploaded so far.
     */
    public const PROGRESS = 'progress';

    /**
     * proxy: (string|array) Pass a string to specify an HTTP proxy, or an
     * array to specify different proxies for different protocols (where the
     * key is the protocol and the value is a proxy string).
     */
    public const PROXY = 'proxy';

    /**
     * query: (array|string) Associative array of query string values to add
     * to the request. This option uses PHP's http_build_query() to create
     * the string representation. Pass a string value if you need more
     * control than what this method provides
     */
    public const QUERY = 'query';

    /**
     * sink: (resource|string|StreamInterface) Where the data of the
     * response is written to. Defaults to a PHP temp stream. Providing a
     * string will write data to a file by the given name.
     */
    public const SINK = 'sink';

    /**
     * synchronous: (bool) Set to true to inform HTTP handlers that you intend
     * on waiting on the response. This can be useful for optimizations. Note
     * that a promise is still returned if you are using one of the async
     * client methods.
     */
    public const SYNCHRONOUS = 'synchronous';

    /**
     * ssl_key: (array|string) Specify the path to a file containing a private
     * SSL key in PEM format. If a password is required, then set to an array
     * containing the path to the SSL key in the first array element followed
     * by the password required for the certificate in the second element.
     */
    public const SSL_KEY = 'ssl_key';

    /**
     * stream: Set to true to attempt to stream a response rather than
     * download it all up-front.
     */
    public const STREAM = 'stream';

    /**
     * verify: (bool|string, default=true) Describes the SSL certificate
     * verification behavior of a request. Set to true to enable SSL
     * certificate verification using the system CA bundle when available
     * (the default). Set to false to disable certificate verification (this
     * is insecure!). Set to a string to provide the path to a CA bundle on
     * disk to enable verification using a custom certificate.
     */
    public const VERIFY = 'verify';

    /**
     * timeout: (float, default=0) Float describing the timeout of the
     * request in seconds. Use 0 to wait indefinitely (the default behavior).
     */
    public const TIMEOUT = 'timeout';

    /**
     * read_timeout: (float, default=default_socket_timeout ini setting) Float describing
     * the body read timeout, for stream requests.
     */
    public const READ_TIMEOUT = 'read_timeout';

    /**
     * version: (float) Specifies the HTTP protocol version to attempt to use.
     */
    public const VERSION = 'version';

    /**
     * force_ip_resolve: (bool) Force client to use only ipv4 or ipv6 protocol
     */
    public const FORCE_IP_RESOLVE = 'force_ip_resolve';
}
