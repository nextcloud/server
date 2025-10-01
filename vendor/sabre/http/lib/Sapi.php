<?php

declare(strict_types=1);

namespace Sabre\HTTP;

/**
 * PHP SAPI.
 *
 * This object is responsible for:
 * 1. Constructing a Request object based on the current HTTP request sent to
 *    the PHP process.
 * 2. Sending the Response object back to the client.
 *
 * It could be said that this class provides a mapping between the Request and
 * Response objects, and php's:
 *
 * * $_SERVER
 * * $_POST
 * * $_FILES
 * * php://input
 * * echo()
 * * header()
 * * php://output
 *
 * You can choose to either call all these methods statically, but you can also
 * instantiate this as an object to allow for polymorphism.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Sapi
{
    /**
     * This static method will create a new Request object, based on the
     * current PHP request.
     */
    public static function getRequest(): Request
    {
        $serverArr = $_SERVER;

        if ('cli' === PHP_SAPI) {
            // If we're running off the CLI, we're going to set some default
            // settings.
            $serverArr['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
            $serverArr['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        }

        $r = self::createFromServerArray($serverArr);
        $r->setBody(fopen('php://input', 'r'));
        $r->setPostData($_POST);

        return $r;
    }

    /**
     * Sends the HTTP response back to a HTTP client.
     *
     * This calls php's header() function and streams the body to php://output.
     */
    public static function sendResponse(ResponseInterface $response)
    {
        header('HTTP/'.$response->getHttpVersion().' '.$response->getStatus().' '.$response->getStatusText());
        foreach ($response->getHeaders() as $key => $value) {
            foreach ($value as $k => $v) {
                if (0 === $k) {
                    header($key.': '.$v);
                } else {
                    header($key.': '.$v, false);
                }
            }
        }

        $body = $response->getBody();
        if (null === $body) {
            return;
        }

        if (is_callable($body)) {
            $body();

            return;
        }

        $contentLength = $response->getHeader('Content-Length');
        if (null !== $contentLength) {
            $output = fopen('php://output', 'wb');
            if (is_resource($body) && 'stream' == get_resource_type($body)) {
                // a workaround to make PHP more possible to use mmap based copy, see https://github.com/sabre-io/http/pull/119
                $left = (int) $contentLength;
                // copy with 4MiB chunks
                $chunk_size = 4 * 1024 * 1024;
                stream_set_chunk_size($output, $chunk_size);
                // If this is a partial response, flush the beginning bytes until the first position that is a multiple of the page size.
                $contentRange = $response->getHeader('Content-Range');
                // Matching "Content-Range: bytes 1234-5678/7890"
                if (null !== $contentRange && preg_match('/^bytes\s([0-9]+)-([0-9]+)\//i', $contentRange, $matches)) {
                    // 4kB should be the default page size on most architectures
                    $pageSize = 4096;
                    $offset = (int) $matches[1];
                    $delta = ($offset % $pageSize) > 0 ? ($pageSize - $offset % $pageSize) : 0;
                    if ($delta > 0) {
                        $left -= stream_copy_to_stream($body, $output, min($delta, $left));
                    }
                }
                while ($left > 0) {
                    $copied = stream_copy_to_stream($body, $output, min($left, $chunk_size));
                    // stream_copy_to_stream($src, $dest, $maxLength) must return the number of bytes copied or false in case of failure
                    // But when the $maxLength is greater than the total number of bytes remaining in the stream,
                    // It returns the negative number of bytes copied
                    // So break the loop in such cases.
                    if ($copied <= 0) {
                        break;
                    }
                    // Abort on client disconnect.
                    // With ignore_user_abort(true), the script is not aborted on client disconnect.
                    // To avoid reading the entire stream and dismissing the data afterward, check between the chunks if the client is still there.
                    if (1 === ignore_user_abort() && 1 === connection_aborted()) {
                        break;
                    }
                    $left -= $copied;
                }
            } else {
                fwrite($output, $body, (int) $contentLength);
            }
        } else {
            file_put_contents('php://output', $body);
        }

        if (is_resource($body)) {
            fclose($body);
        }
    }

    /**
     * This static method will create a new Request object, based on a PHP
     * $_SERVER array.
     *
     * REQUEST_URI and REQUEST_METHOD are required.
     */
    public static function createFromServerArray(array $serverArray): Request
    {
        $headers = [];
        $method = null;
        $url = null;
        $httpVersion = '1.1';

        $protocol = 'http';
        $hostName = 'localhost';

        foreach ($serverArray as $key => $value) {
            $key = (string) $key;
            switch ($key) {
                case 'SERVER_PROTOCOL':
                    if ('HTTP/1.0' === $value) {
                        $httpVersion = '1.0';
                    } elseif ('HTTP/2.0' === $value) {
                        $httpVersion = '2.0';
                    }
                    break;
                case 'REQUEST_METHOD':
                    $method = $value;
                    break;
                case 'REQUEST_URI':
                    $url = $value;
                    break;

                    // These sometimes show up without a HTTP_ prefix
                case 'CONTENT_TYPE':
                    $headers['Content-Type'] = $value;
                    break;
                case 'CONTENT_LENGTH':
                    $headers['Content-Length'] = $value;
                    break;

                    // mod_php on apache will put credentials in these variables.
                    // (fast)cgi does not usually do this, however.
                case 'PHP_AUTH_USER':
                    if (isset($serverArray['PHP_AUTH_PW'])) {
                        $headers['Authorization'] = 'Basic '.base64_encode($value.':'.$serverArray['PHP_AUTH_PW']);
                    }
                    break;

                    // Similarly, mod_php may also screw around with digest auth.
                case 'PHP_AUTH_DIGEST':
                    $headers['Authorization'] = 'Digest '.$value;
                    break;

                    // Apache may prefix the HTTP_AUTHORIZATION header with
                    // REDIRECT_, if mod_rewrite was used.
                case 'REDIRECT_HTTP_AUTHORIZATION':
                    $headers['Authorization'] = $value;
                    break;

                case 'HTTP_HOST':
                    $hostName = $value;
                    $headers['Host'] = $value;
                    break;

                case 'HTTPS':
                    if (!empty($value) && 'off' !== $value) {
                        $protocol = 'https';
                    }
                    break;

                default:
                    if ('HTTP_' === substr($key, 0, 5)) {
                        // It's a HTTP header

                        // Normalizing it to be prettier
                        $header = strtolower(substr($key, 5));

                        // Transforming dashes into spaces, and upper-casing
                        // every first letter.
                        $header = ucwords(str_replace('_', ' ', $header));

                        // Turning spaces into dashes.
                        $header = str_replace(' ', '-', $header);
                        $headers[$header] = $value;
                    }
                    break;
            }
        }

        if (null === $url) {
            throw new \InvalidArgumentException('The _SERVER array must have a REQUEST_URI key');
        }

        if (null === $method) {
            throw new \InvalidArgumentException('The _SERVER array must have a REQUEST_METHOD key');
        }
        $r = new Request($method, $url, $headers);
        $r->setHttpVersion($httpVersion);
        $r->setRawServerData($serverArray);
        $r->setAbsoluteUrl($protocol.'://'.$hostName.$url);

        return $r;
    }
}
