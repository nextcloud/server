<?php
/*
 * Copyright 2010-2012 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */


/*%******************************************************************************************%*/
// CLASS

/**
 * Implements support for Signature v4 (Query).
 *
 * @version 2011.01.03
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class AuthV4Query extends Signer implements Signable
{
	/**
	 * Constructs a new instance of the <AuthV4Query> class.
	 *
	 * @param string $endpoint (Required) The endpoint to direct the request to.
	 * @param string $operation (Required) The operation to execute as a result of this request.
	 * @param array $payload (Required) The options to use as part of the payload in the request.
	 * @param CFCredential $credentials (Required) The credentials to use for signing and making requests.
	 * @return void
	 */
	public function __construct($endpoint, $operation, $payload, CFCredential $credentials)
	{
		parent::__construct($endpoint, $operation, $payload, $credentials);
	}

	/**
	 * Generates a cURL handle with all of the required authentication bits set.
	 *
	 * @return resource A cURL handle ready for executing.
	 */
	public function authenticate()
	{
		// Determine signing values
		$current_time = time();
		$timestamp = gmdate(CFUtilities::DATE_FORMAT_SIGV4, $current_time);

		// Initialize
		$x_amz_target = null;

		$this->headers = array();
		$this->signed_headers = array();
		$this->canonical_headers = array();
		$this->query = array('body' => is_array($this->payload) ? $this->payload : array());

		// Do we have an authentication token?
		if ($this->auth_token)
		{
			$this->headers['X-Amz-Security-Token'] = $this->auth_token;
			$this->query['body']['SecurityToken'] = $this->auth_token;
		}

		// Manage the key-value pairs that are used in the query.
		if (stripos($this->operation, 'x-amz-target') !== false)
		{
			$x_amz_target = trim(str_ireplace('x-amz-target:', '', $this->operation));
		}
		else
		{
			$this->query['body']['Action'] = $this->operation;
		}

		// Only add it if it exists.
		if ($this->api_version)
		{
			$this->query['body']['Version'] = $this->api_version;
		}

		// Do a case-sensitive, natural order sort on the array keys.
		uksort($this->query['body'], 'strcmp');

		// Remove the default scheme from the domain.
		$domain = str_replace(array('http://', 'https://'), '', $this->endpoint);

		// Parse our request.
		$parsed_url = parse_url('http://' . $domain);

		// Set the proper host header.
		if (isset($parsed_url['port']) && (integer) $parsed_url['port'] !== 80 && (integer) $parsed_url['port'] !== 443)
		{
			$host_header = strtolower($parsed_url['host']) . ':' . $parsed_url['port'];
		}
		else
		{
			$host_header = strtolower($parsed_url['host']);
		}

		// Generate the querystring from $this->query
		$this->querystring = $this->util->to_query_string($this->query);

		// Gather information to pass along to other classes.
		$helpers = array(
			'utilities' => $this->utilities_class,
			'request' => $this->request_class,
			'response' => $this->response_class,
		);

		// Compose the request.
		$request_url = ($this->use_ssl ? 'https://' : 'http://') . $domain;
		$request_url .= !isset($parsed_url['path']) ? '/' : '';

		// Instantiate the request class
		$request = new $this->request_class($request_url, $this->proxy, $helpers, $this->credentials);
		$request->set_method('POST');
		$request->set_body($this->canonical_querystring());
		$this->querystring = $this->canonical_querystring();

		$this->headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=utf-8';
		$this->headers['X-Amz-Target'] = $x_amz_target;

		// Pass along registered stream callbacks
		if ($this->registered_streaming_read_callback)
		{
			$request->register_streaming_read_callback($this->registered_streaming_read_callback);
		}

		if ($this->registered_streaming_write_callback)
		{
			$request->register_streaming_write_callback($this->registered_streaming_write_callback);
		}

		// Add authentication headers
		$this->headers['X-Amz-Date'] = $timestamp;
		$this->headers['Content-Length'] = strlen($this->querystring);
		$this->headers['Content-MD5'] = $this->util->hex_to_base64(md5($this->querystring));
		$this->headers['Host'] = $host_header;

		// Sort headers
		uksort($this->headers, 'strnatcasecmp');

		// Add headers to request and compute the string to sign
		foreach ($this->headers as $header_key => $header_value)
		{
			// Strip linebreaks from header values as they're illegal and can allow for security issues
			$header_value = str_replace(array("\r", "\n"), '', $header_value);

			$request->add_header($header_key, $header_value);
			$this->canonical_headers[] = strtolower($header_key) . ':' . $header_value;

			$this->signed_headers[] = strtolower($header_key);
		}

		$this->headers['Authorization'] = $this->authorization($timestamp);

		$request->add_header('Authorization', $this->headers['Authorization']);
		$request->request_headers = $this->headers;

		return $request;
	}

	/**
	 * Generates the authorization string to use for the request.
	 *
	 * @param string $datetime (Required) The current timestamp.
	 * @return string The authorization string.
	 */
	protected function authorization($datetime)
	{
		$access_key_id = $this->key;

		$parts = array();
		$parts[] = "AWS4-HMAC-SHA256 Credential=${access_key_id}/" . $this->credential_string($datetime);
		$parts[] = 'SignedHeaders=' . implode(';', $this->signed_headers);
		$parts[] = 'Signature=' . $this->hex16($this->signature($datetime));

		return implode(',', $parts);
	}

	/**
	 * Calculate the signature.
	 *
	 * @param string $datetime (Required) The current timestamp.
	 * @return string The signature.
	 */
	protected function signature($datetime)
	{
		$k_date        = $this->hmac('AWS4' . $this->secret_key, substr($datetime, 0, 8));
		$k_region      = $this->hmac($k_date, $this->region());
		$k_service     = $this->hmac($k_region, $this->service());
		$k_credentials = $this->hmac($k_service, 'aws4_request');
		$signature     = $this->hmac($k_credentials, $this->string_to_sign($datetime));

		return $signature;
	}

	/**
	 * Calculate the string to sign.
	 *
	 * @param string $datetime (Required) The current timestamp.
	 * @return string The string to sign.
	 */
	protected function string_to_sign($datetime)
	{
		$parts = array();
		$parts[] = 'AWS4-HMAC-SHA256';
		$parts[] = $datetime;
		$parts[] = $this->credential_string($datetime);
		$parts[] = $this->hex16($this->hash($this->canonical_request()));

		$this->string_to_sign = implode("\n", $parts);

		return $this->string_to_sign;
	}

	/**
	 * Generates the credential string to use for signing.
	 *
	 * @param string $datetime (Required) The current timestamp.
	 * @return string The credential string.
	 */
	protected function credential_string($datetime)
	{
		$parts = array();
		$parts[] = substr($datetime, 0, 8);
		$parts[] = $this->region();
		$parts[] = $this->service();
		$parts[] = 'aws4_request';

		return implode('/', $parts);
	}

	/**
	 * Calculate the canonical request.
	 *
	 * @return string The canonical request.
	 */
	protected function canonical_request()
	{
		$parts = array();
		$parts[] = 'POST';
		$parts[] = $this->canonical_uri();
		$parts[] = ''; // $parts[] = $this->canonical_querystring();
		$parts[] = implode("\n", $this->canonical_headers) . "\n";
		$parts[] = implode(';', $this->signed_headers);
		$parts[] = $this->hex16($this->hash($this->canonical_querystring()));

		$this->canonical_request = implode("\n", $parts);

		return $this->canonical_request;
	}

	/**
	 * The region ID to use in the signature.
	 *
	 * @return return The region ID.
	 */
	protected function region()
	{
		$pieces = explode('.', $this->endpoint);

		// Handle cases with single/no region (i.e. service.region.amazonaws.com vs. service.amazonaws.com)
		if (count($pieces < 4))
		{
			return 'us-east-1';
		}

		return $pieces[1];
	}

	/**
	 * The service ID to use in the signature.
	 *
	 * @return return The service ID.
	 */
	protected function service()
	{
		$pieces = explode('.', $this->endpoint);
		return ($pieces[0] === 'email') ? 'ses' : $pieces[0];
	}

	/**
	 * The request URI path.
	 *
	 * @return string The request URI path.
	 */
	protected function canonical_uri()
	{
		return '/';
	}

	/**
	 * The canonical query string.
	 *
	 * @return string The canonical query string.
	 */
	protected function canonical_querystring()
	{
		if (!isset($this->canonical_querystring))
		{
			$this->canonical_querystring = $this->util->to_signable_string($this->query['body']);
		}

		return $this->canonical_querystring;
	}

	/**
	 * Hex16-pack the data.
	 *
	 * @param string $value (Required) The data to hex16 pack.
	 * @return string The hex16-packed data.
	 */
	protected function hex16($value)
	{
		$result = unpack('H*', $value);
		return reset($result);
	}

	/**
	 * Applies HMAC SHA-256 encryption to the string, salted by the key.
	 *
	 * @return string Raw HMAC SHA-256 hashed string.
	 */
	protected function hmac($key, $string)
	{
		return hash_hmac('sha256', $string, $key, true);
	}

	/**
	 * SHA-256 hashes the string.
	 *
	 * @return string Raw SHA-256 hashed string.
	 */
	protected function hash($string)
	{
		return hash('sha256', $string, true);
	}
}
