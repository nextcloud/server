<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * HTTP Request to be executed by apiIO classes. Upon execution, the
 * responseHttpCode, responseHeaders and responseBody will be filled in.
 *
 * @author Chris Chabot <chabotc@google.com>
 * @author Chirag Shah <chirags@google.com>
 *
 */
class Google_HttpRequest {
  const USER_AGENT_SUFFIX = "google-api-php-client/0.6.0";
  private $batchHeaders = array(
    'Content-Type' => 'application/http',
    'Content-Transfer-Encoding' => 'binary',
    'MIME-Version' => '1.0',
    'Content-Length' => ''
  );

  protected $url;
  protected $requestMethod;
  protected $requestHeaders;
  protected $postBody;
  protected $userAgent;

  protected $responseHttpCode;
  protected $responseHeaders;
  protected $responseBody;
  
  public $accessKey;

  public function __construct($url, $method = 'GET', $headers = array(), $postBody = null) {
    $this->setUrl($url);
    $this->setRequestMethod($method);
    $this->setRequestHeaders($headers);
    $this->setPostBody($postBody);

    global $apiConfig;
    if (empty($apiConfig['application_name'])) {
      $this->userAgent = self::USER_AGENT_SUFFIX;
    } else {
      $this->userAgent = $apiConfig['application_name'] . " " . self::USER_AGENT_SUFFIX;
    }
  }

  /**
   * Misc function that returns the base url component of the $url
   * used by the OAuth signing class to calculate the base string
   * @return string The base url component of the $url.
   * @see http://oauth.net/core/1.0a/#anchor13
   */
  public function getBaseUrl() {
    if ($pos = strpos($this->url, '?')) {
      return substr($this->url, 0, $pos);
    }
    return $this->url;
  }

  /**
   * Misc function that returns an array of the query parameters of the current
   * url used by the OAuth signing class to calculate the signature
   * @return array Query parameters in the query string.
   */
  public function getQueryParams() {
    if ($pos = strpos($this->url, '?')) {
      $queryStr = substr($this->url, $pos + 1);
      $params = array();
      parse_str($queryStr, $params);
      return $params;
    }
    return array();
  }

  /**
   * @return string HTTP Response Code.
   */
  public function getResponseHttpCode() {
    return (int) $this->responseHttpCode;
  }

  /**
   * @param int $responseHttpCode HTTP Response Code.
   */
  public function setResponseHttpCode($responseHttpCode) {
    $this->responseHttpCode = $responseHttpCode;
  }

  /**
   * @return $responseHeaders (array) HTTP Response Headers.
   */
  public function getResponseHeaders() {
    return $this->responseHeaders;
  }

  /**
   * @return string HTTP Response Body
   */
  public function getResponseBody() {
    return $this->responseBody;
  }

  /**
   * @param array $headers The HTTP response headers
   * to be normalized.
   */
  public function setResponseHeaders($headers) {
    $headers = Google_Utils::normalize($headers);
    if ($this->responseHeaders) {
      $headers = array_merge($this->responseHeaders, $headers);
    }

    $this->responseHeaders = $headers;
  }

  /**
   * @param string $key
   * @return array|boolean Returns the requested HTTP header or
   * false if unavailable.
   */
  public function getResponseHeader($key) {
    return isset($this->responseHeaders[$key])
        ? $this->responseHeaders[$key]
        : false;
  }

  /**
   * @param string $responseBody The HTTP response body.
   */
  public function setResponseBody($responseBody) {
    $this->responseBody = $responseBody;
  }

  /**
   * @return string $url The request URL.
   */

  public function getUrl() {
    return $this->url;
  }

  /**
   * @return string $method HTTP Request Method.
   */
  public function getRequestMethod() {
    return $this->requestMethod;
  }

  /**
   * @return array $headers HTTP Request Headers.
   */
  public function getRequestHeaders() {
    return $this->requestHeaders;
  }

  /**
   * @param string $key
   * @return array|boolean Returns the requested HTTP header or
   * false if unavailable.
   */
  public function getRequestHeader($key) {
    return isset($this->requestHeaders[$key])
        ? $this->requestHeaders[$key]
        : false;
  }

  /**
   * @return string $postBody HTTP Request Body.
   */
  public function getPostBody() {
    return $this->postBody;
  }

  /**
   * @param string $url the url to set
   */
  public function setUrl($url) {
    if (substr($url, 0, 4) == 'http') {
      $this->url = $url;
    } else {
      // Force the path become relative.
      if (substr($url, 0, 1) !== '/') {
        $url = '/' . $url;
      }
      global $apiConfig;
      $this->url = $apiConfig['basePath'] . $url;
    }
  }

  /**
   * @param string $method Set he HTTP Method and normalize
   * it to upper-case, as required by HTTP.
   *
   */
  public function setRequestMethod($method) {
    $this->requestMethod = strtoupper($method);
  }

  /**
   * @param array $headers The HTTP request headers
   * to be set and normalized.
   */
  public function setRequestHeaders($headers) {
    $headers = Google_Utils::normalize($headers);
    if ($this->requestHeaders) {
      $headers = array_merge($this->requestHeaders, $headers);
    }
    $this->requestHeaders = $headers;
  }

  /**
   * @param string $postBody the postBody to set
   */
  public function setPostBody($postBody) {
    $this->postBody = $postBody;
  }

  /**
   * Set the User-Agent Header.
   * @param string $userAgent The User-Agent.
   */
  public function setUserAgent($userAgent) {
    $this->userAgent = $userAgent;
  }

  /**
   * @return string The User-Agent.
   */
  public function getUserAgent() {
    return $this->userAgent;
  }

  /**
   * Returns a cache key depending on if this was an OAuth signed request
   * in which case it will use the non-signed url and access key to make this
   * cache key unique per authenticated user, else use the plain request url
   * @return string The md5 hash of the request cache key.
   */
  public function getCacheKey() {
    $key = $this->getUrl();

    if (isset($this->accessKey)) {
      $key .= $this->accessKey;
    }

    if (isset($this->requestHeaders['authorization'])) {
      $key .= $this->requestHeaders['authorization'];
    }

    return md5($key);
  }

  public function getParsedCacheControl() {
    $parsed = array();
    $rawCacheControl = $this->getResponseHeader('cache-control');
    if ($rawCacheControl) {
      $rawCacheControl = str_replace(', ', '&', $rawCacheControl);
      parse_str($rawCacheControl, $parsed);
    }

    return $parsed;
  }

  /**
   * @param string $id
   * @return string A string representation of the HTTP Request.
   */
  public function toBatchString($id) {
    $str = '';
    foreach($this->batchHeaders as $key => $val) {
      $str .= $key . ': ' . $val . "\n";
    }

    $str .= "Content-ID: $id\n";
    $str .= "\n";

    $path = parse_url($this->getUrl(), PHP_URL_PATH);
    $str .= $this->getRequestMethod() . ' ' . $path . " HTTP/1.1\n";
    foreach($this->getRequestHeaders() as $key => $val) {
      $str .= $key . ': ' . $val . "\n";
    }

    if ($this->getPostBody()) {
      $str .= "\n";
      $str .= $this->getPostBody();
    }

    return $str;
  }
}
