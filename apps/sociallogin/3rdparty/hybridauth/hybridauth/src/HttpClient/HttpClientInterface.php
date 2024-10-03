<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\HttpClient;

/**
 * Hybridauth Http clients interface
 */
interface HttpClientInterface
{
    /**
     * Send request to the remote server
     *
     * Returns the result (Raw response from the server) on success, FALSE on failure
     *
     * @param string $uri
     * @param string $method
     * @param array $parameters
     * @param array $headers
     * @param bool $multipart
     *
     * @return mixed
     */
    public function request($uri, $method = 'GET', $parameters = [], $headers = [], $multipart = false);

    /**
     * Returns raw response from the server on success, FALSE on failure
     *
     * @return mixed
     */
    public function getResponseBody();

    /**
     * Retriever the headers returned in the response
     *
     * @return array
     */
    public function getResponseHeader();

    /**
     * Returns latest request HTTP status code
     *
     * @return int
     */
    public function getResponseHttpCode();

    /**
     * Returns latest error encountered by the client
     * This can be either a code or error message
     *
     * @return mixed
     */
    public function getResponseClientError();
}
