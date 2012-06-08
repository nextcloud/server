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
 * Simplifies the process of signing JSON policy documents.
 *
 * @version 2011.04.25
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFPolicy
{
	/**
	 * Stores the object that contains the authentication credentials.
	 */
	public $auth;

	/**
	 * Stores the policy object that we're working with.
	 */
	public $json_policy;

	/**
	 * Constructs a new instance of this class.
	 *
	 * @param CFRuntime $auth (Required) An instance of any authenticated AWS object that is an instance of <CFRuntime> (e.g. <AmazonEC2>, <AmazonS3>).
	 * @param string|array $policy (Required) The associative array representing the S3 policy to use, or a string of JSON content.
	 * @return $this A reference to the current instance.
	 * @link http://docs.amazonwebservices.com/AmazonS3/2006-03-01/dev/index.html?HTTPPOSTForms.html S3 Policies
	 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/index.html?AccessPolicyLanguage.html Access Policy Language
	 */
	public function __construct($auth, $policy)
	{
		$this->auth = $auth;

		if (is_array($policy)) // We received an associative array...
		{
			$this->json_policy = json_encode($policy);
		}
		else // We received a valid, parseable JSON string...
		{
			$this->json_policy = json_encode(json_decode($policy, true));
		}

		return $this;
	}

	/**
	 * Alternate approach to constructing a new instance. Supports chaining.
	 *
	 * @param CFRuntime $auth (Required) An instance of any authenticated AWS object that is an instance of <CFRuntime> (e.g. <AmazonEC2>, <AmazonS3>).
	 * @param string|array $policy (Required) The associative array representing the S3 policy to use, or a string of JSON content.
	 * @return $this A reference to the current instance.
	 */
	public static function init($auth, $policy)
	{
		if (version_compare(PHP_VERSION, '5.3.0', '<'))
		{
			throw new Exception('PHP 5.3 or newer is required to instantiate a new class with CLASS::init().');
		}

		$self = get_called_class();
		return new $self($auth, $policy);
	}

	/**
	 * Get the key from the authenticated instance.
	 *
	 * @return string The key from the authenticated instance.
	 */
	public function get_key()
	{
		return $this->auth->key;
	}

	/**
	 * Base64-encodes the JSON string.
	 *
	 * @return string The Base64-encoded version of the JSON string.
	 */
	public function get_policy()
	{
		return base64_encode($this->json_policy);
	}

	/**
	 * Gets the JSON string with the whitespace removed.
	 *
	 * @return string The JSON string without extraneous whitespace.
	 */
	public function get_json()
	{
		return $this->json_policy;
	}

	/**
	 * Gets the JSON string with the whitespace removed.
	 *
	 * @return string The Base64-encoded, signed JSON string.
	 */
	public function get_policy_signature()
	{
		return base64_encode(hash_hmac('sha1', $this->get_policy(), $this->auth->secret_key));
	}

	/**
	 * Decode a policy that was returned from the service.
	 *
	 * @param string $response (Required) The policy returned by AWS that you want to decode into an object.
	 * @return string The Base64-encoded, signed JSON string.
	 */
	public static function decode_policy($response)
	{
		return json_decode(urldecode($response), true);
	}
}
