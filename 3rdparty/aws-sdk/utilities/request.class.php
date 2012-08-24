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
 * Wraps the underlying `RequestCore` class with some AWS-specific customizations.
 *
 * @version 2011.12.02
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFRequest extends RequestCore
{
	/**
	 * The default class to use for HTTP Requests (defaults to <CFRequest>).
	 */
	public $request_class = 'CFRequest';

	/**
	 * The default class to use for HTTP Responses (defaults to <CFResponse>).
	 */
	public $response_class = 'CFResponse';

	/**
	 * The active credential set.
	 */
	public $credentials;


	/*%******************************************************************************************%*/
	// CONSTRUCTOR

	/**
	 * Constructs a new instance of this class.
	 *
	 * @param string $url (Optional) The URL to request or service endpoint to query.
	 * @param string $proxy (Optional) The faux-url to use for proxy settings. Takes the following format: `proxy://user:pass@hostname:port`
	 * @param array $helpers (Optional) An associative array of classnames to use for request, and response functionality. Gets passed in automatically by the calling class.
	 * @param CFCredential $credentials (Required) The credentials to use for signing and making requests.
	 * @return $this A reference to the current instance.
	 */
	public function __construct($url = null, $proxy = null, $helpers = null, CFCredential $credentials = null)
	{
		parent::__construct($url, $proxy, $helpers);

		// Standard settings for all requests
		$this->set_useragent(CFRUNTIME_USERAGENT);
		$this->credentials = $credentials;
		$this->cacert_location = ($this->credentials['certificate_authority'] ? $this->credentials['certificate_authority'] : false);

		return $this;
	}
}
