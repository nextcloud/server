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
 * Curl based implementation of apiIO.
 *
 * @author Chris Chabot <chabotc@google.com>
 * @author Chirag Shah <chirags@google.com>
 */

require_once 'Google_CacheParser.php';

class Google_CurlIO implements Google_IO {
  const CONNECTION_ESTABLISHED = "HTTP/1.0 200 Connection established\r\n\r\n";
  const FORM_URLENCODED = 'application/x-www-form-urlencoded';

  private static $ENTITY_HTTP_METHODS = array("POST" => null, "PUT" => null);
  private static $HOP_BY_HOP = array(
      'connection', 'keep-alive', 'proxy-authenticate', 'proxy-authorization',
      'te', 'trailers', 'transfer-encoding', 'upgrade');

  private $curlParams = array (
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => 0,
      CURLOPT_FAILONERROR => false,
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_HEADER => true,
      CURLOPT_VERBOSE => false,
  );

  /**
   * Perform an authenticated / signed apiHttpRequest.
   * This function takes the apiHttpRequest, calls apiAuth->sign on it
   * (which can modify the request in what ever way fits the auth mechanism)
   * and then calls apiCurlIO::makeRequest on the signed request
   *
   * @param Google_HttpRequest $request
   * @return Google_HttpRequest The resulting HTTP response including the
   * responseHttpCode, responseHeaders and responseBody.
   */
  public function authenticatedRequest(Google_HttpRequest $request) {
    $request = Google_Client::$auth->sign($request);
    return $this->makeRequest($request);
  }

  /**
   * Execute a apiHttpRequest
   *
   * @param Google_HttpRequest $request the http request to be executed
   * @return Google_HttpRequest http request with the response http code, response
   * headers and response body filled in
   * @throws Google_IOException on curl or IO error
   */
  public function makeRequest(Google_HttpRequest $request) {
    // First, check to see if we have a valid cached version.
    $cached = $this->getCachedRequest($request);
    if ($cached !== false) {
      if (Google_CacheParser::mustRevalidate($cached)) {
        $addHeaders = array();
        if ($cached->getResponseHeader('etag')) {
          // [13.3.4] If an entity tag has been provided by the origin server,
          // we must use that entity tag in any cache-conditional request.
          $addHeaders['If-None-Match'] = $cached->getResponseHeader('etag');
        } elseif ($cached->getResponseHeader('date')) {
          $addHeaders['If-Modified-Since'] = $cached->getResponseHeader('date');
        }

        $request->setRequestHeaders($addHeaders);
      } else {
        // No need to revalidate the request, return it directly
        return $cached;
      }
    }

    if (array_key_exists($request->getRequestMethod(),
          self::$ENTITY_HTTP_METHODS)) {
      $request = $this->processEntityRequest($request);
    }

    $ch = curl_init();
    curl_setopt_array($ch, $this->curlParams);
    curl_setopt($ch, CURLOPT_URL, $request->getUrl());
    if ($request->getPostBody()) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getPostBody());
    }

    $requestHeaders = $request->getRequestHeaders();
    if ($requestHeaders && is_array($requestHeaders)) {
      $parsed = array();
      foreach ($requestHeaders as $k => $v) {
        $parsed[] = "$k: $v";
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, $parsed);
    }

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->getRequestMethod());
    curl_setopt($ch, CURLOPT_USERAGENT, $request->getUserAgent());
    $respData = curl_exec($ch);

    // Retry if certificates are missing.
    if (curl_errno($ch) == CURLE_SSL_CACERT) {
      error_log('SSL certificate problem, verify that the CA cert is OK.'
        . ' Retrying with the CA cert bundle from google-api-php-client.');
      curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacerts.pem');
      $respData = curl_exec($ch);
    }

    $respHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $respHttpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrorNum = curl_errno($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
    if ($curlErrorNum != CURLE_OK) {
      throw new Google_IOException("HTTP Error: ($respHttpCode) $curlError");
    }

    // Parse out the raw response into usable bits
    list($responseHeaders, $responseBody) =
          self::parseHttpResponse($respData, $respHeaderSize);

    if ($respHttpCode == 304 && $cached) {
      // If the server responded NOT_MODIFIED, return the cached request.
      if (isset($responseHeaders['connection'])) {
        $hopByHop = array_merge(
          self::$HOP_BY_HOP,
          explode(',', $responseHeaders['connection'])
        );

        $endToEnd = array();
        foreach($hopByHop as $key) {
          if (isset($responseHeaders[$key])) {
            $endToEnd[$key] = $responseHeaders[$key];
          }
        }
        $cached->setResponseHeaders($endToEnd);
      }
      return $cached;
    }

    // Fill in the apiHttpRequest with the response values
    $request->setResponseHttpCode($respHttpCode);
    $request->setResponseHeaders($responseHeaders);
    $request->setResponseBody($responseBody);
    // Store the request in cache (the function checks to see if the request
    // can actually be cached)
    $this->setCachedRequest($request);
    // And finally return it
    return $request;
  }

  /**
   * @visible for testing.
   * Cache the response to an HTTP request if it is cacheable.
   * @param Google_HttpRequest $request
   * @return bool Returns true if the insertion was successful.
   * Otherwise, return false.
   */
  public function setCachedRequest(Google_HttpRequest $request) {
    // Determine if the request is cacheable.
    if (Google_CacheParser::isResponseCacheable($request)) {
      Google_Client::$cache->set($request->getCacheKey(), $request);
      return true;
    }

    return false;
  }

  /**
   * @visible for testing.
   * @param Google_HttpRequest $request
   * @return Google_HttpRequest|bool Returns the cached object or
   * false if the operation was unsuccessful.
   */
  public function getCachedRequest(Google_HttpRequest $request) {
    if (false == Google_CacheParser::isRequestCacheable($request)) {
      false;
    }

    return Google_Client::$cache->get($request->getCacheKey());
  }

  /**
   * @param $respData
   * @param $headerSize
   * @return array
   */
  public static function parseHttpResponse($respData, $headerSize) {
    if (stripos($respData, self::CONNECTION_ESTABLISHED) !== false) {
      $respData = str_ireplace(self::CONNECTION_ESTABLISHED, '', $respData);
    }

    if ($headerSize) {
      $responseBody = substr($respData, $headerSize);
      $responseHeaders = substr($respData, 0, $headerSize);
    } else {
      list($responseHeaders, $responseBody) = explode("\r\n\r\n", $respData, 2);
    }

    $responseHeaders = self::parseResponseHeaders($responseHeaders);
    return array($responseHeaders, $responseBody);
  }

  public static function parseResponseHeaders($rawHeaders) {
    $responseHeaders = array();

    $responseHeaderLines = explode("\r\n", $rawHeaders);
    foreach ($responseHeaderLines as $headerLine) {
      if ($headerLine && strpos($headerLine, ':') !== false) {
        list($header, $value) = explode(': ', $headerLine, 2);
        $header = strtolower($header);
        if (isset($responseHeaders[$header])) {
          $responseHeaders[$header] .= "\n" . $value;
        } else {
          $responseHeaders[$header] = $value;
        }
      }
    }
    return $responseHeaders;
  }

  /**
   * @visible for testing
   * Process an http request that contains an enclosed entity.
   * @param Google_HttpRequest $request
   * @return Google_HttpRequest Processed request with the enclosed entity.
   */
  public function processEntityRequest(Google_HttpRequest $request) {
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
   * Set options that update cURL's default behavior.
   * The list of accepted options are:
   * {@link http://php.net/manual/en/function.curl-setopt.php]
   *
   * @param array $optCurlParams Multiple options used by a cURL session.
   */
  public function setOptions($optCurlParams) {
    foreach ($optCurlParams as $key => $val) {
      $this->curlParams[$key] = $val;
    }
  }
}