<?php
/* Copyright (c) 2009 Google Inc.
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
 *
 * Author: Eric Bidelman <e.bidelman@google.com>
 */

$PRIV_KEY_FILE = '/path/to/your/rsa_private_key.pem';

// OAuth library - http://oauth.googlecode.com/svn/code/php/
require_once('OAuth.php');

// Google's accepted signature methods
$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
$rsa_method = new OAuthSignatureMethod_RSA_SHA1();
$SIG_METHODS = array($rsa_method->get_name() => $rsa_method,
                     $hmac_method->get_name() => $hmac_method);

/**
 * Makes an HTTP request to the specified URL
 *
 * @param string $http_method The HTTP method (GET, POST, PUT, DELETE)
 * @param string $url Full URL of the resource to access
 * @param array $extraHeaders (optional) Additional headers to include in each
 *     request. Elements are header/value pair strings ('Host: example.com')
 * @param string $postData (optional) POST/PUT request body
 * @param bool $returnResponseHeaders True if resp. headers should be returned.
 * @return string Response body from the server
 */
function send_signed_request($http_method, $url, $extraHeaders=null,
                             $postData=null, $returnResponseHeaders=true) {
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_FAILONERROR, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

  // Return request headers in the reponse
//   curl_setopt($curl, CURLINFO_HEADER_OUT, true);

  // Return response headers ni the response?
  if ($returnResponseHeaders) {
    curl_setopt($curl, CURLOPT_HEADER, true);
  }

  $headers = array();
  //$headers[] = 'GData-Version: 2.0';  // use GData v2 by default
  if (is_array($extraHeaders)) {
    $headers = array_merge($headers, $extraHeaders);
  }

  // Setup default curl options for each type of HTTP request.
  // This is also a great place to add additional headers for each request.
  switch($http_method) {
    case 'GET':
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      break;
    case 'POST':
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
      break;
    case 'PUT':
      $headers[] = 'If-Match: *';
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_method);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
      break;
    case 'DELETE':
      $headers[] = 'If-Match: *';
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_method);
      break;
    default:
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  }

  // Execute the request.  If an error occures, fill the response body with it.
  $response = curl_exec($curl);
  if (!$response) {
    $response = curl_error($curl);
  }

  // Add server's response headers to our response body
 $response = curl_getinfo($curl, CURLINFO_HEADER_OUT) . $response;

  curl_close($curl);

  return $response;
}

/**
* Takes XML as a string and returns it nicely indented
*
* @param string $xml The xml to beautify
* @param boolean $html_output True if returned XML should be escaped for HTML.
* @return string The beautified xml
*/
function xml_pretty_printer($xml, $html_output=false) {
  $xml_obj = new SimpleXMLElement($xml);
  $level = 2;

  // Get an array containing each XML element
  $xml = explode("\n", preg_replace('/>\s*</', ">\n<", $xml_obj->asXML()));

  // Hold current indentation level
  $indent = 0;

  $pretty = array();

  // Shift off opening XML tag if present
  if (count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {
    $pretty[] = array_shift($xml);
  }

  foreach ($xml as $el) {
    if (preg_match('/^<([\w])+[^>\/]*>$/U', $el)) {
      // opening tag, increase indent
      $pretty[] = str_repeat(' ', $indent) . $el;
      $indent += $level;
    } else {
      if (preg_match('/^<\/.+>$/', $el)) {
        $indent -= $level;  // closing tag, decrease indent
      }
      if ($indent < 0) {
        $indent += $level;
      }
      $pretty[] = str_repeat(' ', $indent) . $el;
    }
  }

  $xml = implode("\n", $pretty);
  return $html_output ? htmlentities($xml) : $xml;
}

/**
 * Joins key/value pairs by $inner_glue and each pair together by $outer_glue.
 *
 * Example: implode_assoc('=', '&', array('a' => 1, 'b' => 2)) === 'a=1&b=2'
 *
 * @param string $inner_glue What to implode each key/value pair with
 * @param string $outer_glue What to impode each key/value string subset with
 * @param array $array Associative array of query parameters
 * @return string Urlencoded string of query parameters
 */
function implode_assoc($inner_glue, $outer_glue, $array) {
  $output = array();
  foreach($array as $key => $item) {
    $output[] = $key . $inner_glue . urlencode($item);
  }
  return implode($outer_glue, $output);
}

/**
 * Explodes a string of key/value url parameters into an associative array.
 * This method performs the compliment operations of implode_assoc().
 *
 * Example: explode_assoc('=', '&', 'a=1&b=2') === array('a' => 1, 'b' => 2)
 *
 * @param string $inner_glue What each key/value pair is joined with
 * @param string $outer_glue What each set of key/value pairs is joined with.
 * @param array $array Associative array of query parameters
 * @return array Urlencoded string of query parameters
 */
function explode_assoc($inner_glue, $outer_glue, $params) {
  $tempArr = explode($outer_glue, $params);
  foreach($tempArr as $val) {
    $pos = strpos($val, $inner_glue);
    $key = substr($val, 0, $pos);
    $array2[$key] = substr($val, $pos + 1, strlen($val));
  }
  return $array2;
}

?>