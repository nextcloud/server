<?php

namespace Guzzle\Log;

use Guzzle\Http\Curl\CurlHandle;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Response;

/**
 * Message formatter used in various places in the framework
 *
 * Format messages using a template that can contain the the following variables:
 *
 * - {request}:       Full HTTP request message
 * - {response}:      Full HTTP response message
 * - {ts}:            Timestamp
 * - {host}:          Host of the request
 * - {method}:        Method of the request
 * - {url}:           URL of the request
 * - {host}:          Host of the request
 * - {protocol}:      Request protocol
 * - {version}:       Protocol version
 * - {resource}:      Resource of the request (path + query + fragment)
 * - {port}:          Port of the request
 * - {hostname}:      Hostname of the machine that sent the request
 * - {code}:          Status code of the response (if available)
 * - {phrase}:        Reason phrase of the response  (if available)
 * - {curl_error}:    Curl error message (if available)
 * - {curl_code}:     Curl error code (if available)
 * - {curl_stderr}:   Curl standard error (if available)
 * - {connect_time}:  Time in seconds it took to establish the connection (if available)
 * - {total_time}:    Total transaction time in seconds for last transfer (if available)
 * - {req_header_*}:  Replace `*` with the lowercased name of a request header to add to the message
 * - {res_header_*}:  Replace `*` with the lowercased name of a response header to add to the message
 * - {req_body}:      Request body
 * - {res_body}:      Response body
 */
class MessageFormatter
{
    const DEFAULT_FORMAT = "{hostname} {req_header_User-Agent} - [{ts}] \"{method} {resource} {protocol}/{version}\" {code} {res_header_Content-Length}";
    const DEBUG_FORMAT = ">>>>>>>>\n{request}\n<<<<<<<<\n{response}\n--------\n{curl_stderr}";
    const SHORT_FORMAT = '[{ts}] "{method} {resource} {protocol}/{version}" {code}';

    /**
     * @var string Template used to format log messages
     */
    protected $template;

    /**
     * @param string $template Log message template
     */
    public function __construct($template = self::DEFAULT_FORMAT)
    {
        $this->template = $template ?: self::DEFAULT_FORMAT;
    }

    /**
     * Set the template to use for logging
     *
     * @param string $template Log message template
     *
     * @return self
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Returns a formatted message
     *
     * @param RequestInterface $request    Request that was sent
     * @param Response         $response   Response that was received
     * @param CurlHandle       $handle     Curl handle associated with the message
     * @param array            $customData Associative array of custom template data
     *
     * @return string
     */
    public function format(
        RequestInterface $request,
        Response $response = null,
        CurlHandle $handle = null,
        array $customData = array()
    ) {
        $cache = $customData;

        return preg_replace_callback(
            '/{\s*([A-Za-z_\-\.0-9]+)\s*}/',
            function (array $matches) use ($request, $response, $handle, &$cache) {

                if (array_key_exists($matches[1], $cache)) {
                    return $cache[$matches[1]];
                }

                $result = '';
                switch ($matches[1]) {
                    case 'request':
                        $result = (string) $request;
                        break;
                    case 'response':
                        $result = (string) $response;
                        break;
                    case 'req_body':
                        $result = $request instanceof EntityEnclosingRequestInterface
                            ? (string) $request->getBody() : '';
                        break;
                    case 'res_body':
                        $result = $response ? $response->getBody(true) : '';
                        break;
                    case 'ts':
                        $result = gmdate('c');
                        break;
                    case 'method':
                        $result = $request->getMethod();
                        break;
                    case 'url':
                        $result = (string) $request->getUrl();
                        break;
                    case 'resource':
                        $result = $request->getResource();
                        break;
                    case 'protocol':
                        $result = 'HTTP';
                        break;
                    case 'version':
                        $result = $request->getProtocolVersion();
                        break;
                    case 'host':
                        $result = $request->getHost();
                        break;
                    case 'hostname':
                        $result = gethostname();
                        break;
                    case 'port':
                        $result = $request->getPort();
                        break;
                    case 'code':
                        $result = $response ? $response->getStatusCode() : '';
                        break;
                    case 'phrase':
                        $result = $response ? $response->getReasonPhrase() : '';
                        break;
                    case 'connect_time':
                        $result = $handle && $handle->getInfo(CURLINFO_CONNECT_TIME)
                            ? $handle->getInfo(CURLINFO_CONNECT_TIME)
                            : ($response ? $response->getInfo('connect_time') : '');
                        break;
                    case 'total_time':
                        $result = $handle && $handle->getInfo(CURLINFO_TOTAL_TIME)
                            ? $handle->getInfo(CURLINFO_TOTAL_TIME)
                            : ($response ? $response->getInfo('total_time') : '');
                        break;
                    case 'curl_error':
                        $result = $handle ? $handle->getError() : '';
                        break;
                    case 'curl_code':
                        $result = $handle ? $handle->getErrorNo() : '';
                        break;
                    case 'curl_stderr':
                        $result =  $handle ? $handle->getStderr() : '';
                        break;
                    default:
                        if (strpos($matches[1], 'req_header_') === 0) {
                            $result = $request->getHeader(substr($matches[1], 11));
                        } elseif ($response && strpos($matches[1], 'res_header_') === 0) {
                            $result = $response->getHeader(substr($matches[1], 11));
                        }
                }

                $cache[$matches[1]] = $result;
                return $result;
            },
            $this->template
        );
    }
}
