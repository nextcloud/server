<?php
/*
 * Copyright 2013 Google Inc.
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
 * Abstract IO base class
 */

require_once 'Google/Client.php';
require_once 'Google/IO/Exception.php';
require_once 'Google/Http/CacheParser.php';
require_once 'Google/Http/Request.php';

abstract class Google_IO_Abstract
{
  const FORM_URLENCODED = 'application/x-www-form-urlencoded';
  const CONNECTION_ESTABLISHED = "HTTP/1.0 200 Connection established\r\n\r\n";

  /** @var Google_Client */
  protected $client;

  public function __construct(Google_Client $client)
  {
    $this->client = $client;
  }

  /**
   * Executes a Google_Http_Request and returns the resulting populated Google_Http_Request
   * @param Google_Http_Request $request
   * @return Google_Http_Request $request
   */
  abstract public function makeRequest(Google_Http_Request $request);

  /**
   * Set options that update the transport implementation's behavior.
   * @param $options
   */
  abstract public function setOptions($options);

  /**
   * @visible for testing.
   * Cache the response to an HTTP request if it is cacheable.
   * @param Google_Http_Request $request
   * @return bool Returns true if the insertion was successful.
   * Otherwise, return false.
   */
  public function setCachedRequest(Google_Http_Request $request)
  {
    // Determine if the request is cacheable.
    if (Google_Http_CacheParser::isResponseCacheable($request)) {
      $this->client->getCache()->set($request->getCacheKey(), $request);
      return true;
    }

    return false;
  }

  /**
   * @visible for testing.
   * @param Google_Http_Request $request
   * @return Google_Http_Request|bool Returns the cached object or
   * false if the operation was unsuccessful.
   */
  public function getCachedRequest(Google_Http_Request $request)
  {
    if (false === Google_Http_CacheParser::isRequestCacheable($request)) {
      return false;
    }

    return $this->client->getCache()->get($request->getCacheKey());
  }

  /**
   * @visible for testing
   * Process an http request that contains an enclosed entity.
   * @param Google_Http_Request $request
   * @return Google_Http_Request Processed request with the enclosed entity.
   */
  public function processEntityRequest(Google_Http_Request $request)
  {
    $postBody = $request->getPostBody();
    $contentType = $request->getRequestHeader("content-type");

    // Set the default content-type as application/x-www-form-urlencoded.
    if (false == $contentType) {
      $contentType = self::FORM_URLENCODED;
      $request->setRequestHeaders(array('content-type' => $contentType));
    }

    // Force the payload to match the content-type asserted in the header.
    if ($contentType == self::FORM_URLENCODED && is_array($postBody)) {
      $postBody = http_build_query($postBody, '', '&');
      $request->setPostBody($postBody);
    }

    // Make sure the content-length header is set.
    if (!$postBody || is_string($postBody)) {
      $postsLength = strlen($postBody);
      $request->setRequestHeaders(array('content-length' => $postsLength));
    }

    return $request;
  }

  /**
   * Check if an already cached request must be revalidated, and if so update
   * the request with the correct ETag headers.
   * @param Google_Http_Request $cached A previously cached response.
   * @param Google_Http_Request $request The outbound request.
   * return bool If the cached object needs to be revalidated, false if it is
   * still current and can be re-used.
   */
  protected function checkMustRevalidateCachedRequest($cached, $request)
  {
    if (Google_Http_CacheParser::mustRevalidate($cached)) {
      $addHeaders = array();
      if ($cached->getResponseHeader('etag')) {
        // [13.3.4] If an entity tag has been provided by the origin server,
        // we must use that entity tag in any cache-conditional request.
        $addHeaders['If-None-Match'] = $cached->getResponseHeader('etag');
      } elseif ($cached->getResponseHeader('date')) {
        $addHeaders['If-Modified-Since'] = $cached->getResponseHeader('date');
      }

      $request->setRequestHeaders($addHeaders);
      return true;
    } else {
      return false;
    }
  }

  /**
   * Update a cached request, using the headers from the last response.
   * @param Google_HttpRequest $cached A previously cached response.
   * @param mixed Associative array of response headers from the last request.
   */
  protected function updateCachedRequest($cached, $responseHeaders)
  {
    if (isset($responseHeaders['connection'])) {
      $hopByHop = array_merge(
          self::$HOP_BY_HOP,
          explode(
              ',',
              $responseHeaders['connection']
          )
      );

      $endToEnd = array();
      foreach ($hopByHop as $key) {
        if (isset($responseHeaders[$key])) {
          $endToEnd[$key] = $responseHeaders[$key];
        }
      }
      $cached->setResponseHeaders($endToEnd);
    }
  }

  /**
   * Used by the IO lib and also the batch processing.
   *
   * @param $respData
   * @param $headerSize
   * @return array
   */
  public function parseHttpResponse($respData, $headerSize)
  {
    if (stripos($respData, self::CONNECTION_ESTABLISHED) !== false) {
      $respData = str_ireplace(self::CONNECTION_ESTABLISHED, '', $respData);
    }

    if ($headerSize) {
      $responseBody = substr($respData, $headerSize);
      $responseHeaders = substr($respData, 0, $headerSize);
    } else {
      list($responseHeaders, $responseBody) = explode("\r\n\r\n", $respData, 2);
    }

    $responseHeaders = $this->getHttpResponseHeaders($responseHeaders);
    return array($responseHeaders, $responseBody);
  }

  /**
   * Parse out headers from raw headers
   * @param rawHeaders array or string
   * @return array
   */
  public function getHttpResponseHeaders($rawHeaders)
  {
    if (is_array($rawHeaders)) {
      return $this->parseArrayHeaders($rawHeaders);
    } else {
      return $this->parseStringHeaders($rawHeaders);
    }
  }

  private function parseStringHeaders($rawHeaders)
  {
    $headers = array();

    $responseHeaderLines = explode("\r\n", $rawHeaders);
    foreach ($responseHeaderLines as $headerLine) {
      if ($headerLine && strpos($headerLine, ':') !== false) {
        list($header, $value) = explode(': ', $headerLine, 2);
        $header = strtolower($header);
        if (isset($responseHeaders[$header])) {
          $headers[$header] .= "\n" . $value;
        } else {
          $headers[$header] = $value;
        }
      }
    }
    return $headers;
  }

  private function parseArrayHeaders($rawHeaders)
  {
    $header_count = count($rawHeaders);
    $headers = array();

    for ($i = 0; $i < $header_count; $i++) {
      $header = $rawHeaders[$i];
      // Times will have colons in - so we just want the first match.
      $header_parts = explode(': ', $header, 2);
      if (count($header_parts) == 2) {
        $headers[$header_parts[0]] = $header_parts[1];
      }
    }

    return $headers;
  }
}
