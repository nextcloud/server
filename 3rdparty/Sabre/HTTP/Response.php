<?php

/**
 * Sabre_HTTP_Response
 *
 * @package Sabre
 * @subpackage HTTP 
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_HTTP_Response {

    /**
     * Returns a full HTTP status message for an HTTP status code
     *
     * @param int $code
     * @return string
     */
    public function getStatusMessage($code) {

        $msg = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authorative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status', // RFC 4918
            208 => 'Already Reported', // RFC 5842
            226 => 'IM Used', // RFC 3229
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Reserved',
            307 => 'Temporary Redirect',
            400 => 'Bad request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot', // RFC 2324
            422 => 'Unprocessable Entity', // RFC 4918
            423 => 'Locked', // RFC 4918
            424 => 'Failed Dependency', // RFC 4918
            426 => 'Upgrade required',
            428 => 'Precondition required', // draft-nottingham-http-new-status
            429 => 'Too Many Requests', // draft-nottingham-http-new-status
            431 => 'Request Header Fields Too Large', // draft-nottingham-http-new-status
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version not supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage', // RFC 4918
            508 => 'Loop Detected', // RFC 5842
            509 => 'Bandwidth Limit Exceeded', // non-standard
            510 => 'Not extended',
            511 => 'Network Authentication Required', // draft-nottingham-http-new-status
       );

       return 'HTTP/1.1 ' . $code . ' ' . $msg[$code];

    }

    /**
     * Sends an HTTP status header to the client
     *
     * @param int $code HTTP status code
     * @return bool
     */
    public function sendStatus($code) {

        if (!headers_sent())
            return header($this->getStatusMessage($code));
        else return false;

    }

    /**
     * Sets an HTTP header for the response
     *
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @return bool
     */
    public function setHeader($name, $value, $replace = true) {

        $value = str_replace(array("\r","\n"),array('\r','\n'),$value);
        if (!headers_sent())
            return header($name . ': ' . $value, $replace);
        else return false;

    }

    /**
     * Sets a bunch of HTTP Headers
     *
     * headersnames are specified as keys, value in the array value
     *
     * @param array $headers
     * @return void
     */
    public function setHeaders(array $headers) {

        foreach($headers as $key=>$value)
            $this->setHeader($key, $value);

    }

    /**
     * Sends the entire response body
     *
     * This method can accept either an open filestream, or a string.
     *
     * @param mixed $body
     * @return void
     */
    public function sendBody($body) {

        if (is_resource($body)) {

            fpassthru($body);

        } else {

            // We assume a string
            echo $body;

        }

    }

}
