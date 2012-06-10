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
// EXCEPTIONS

/**
 * Default CFBatchRequest Exception.
 */
class CFBatchRequest_Exception extends Exception {}


/*%******************************************************************************************%*/
// CLASS

/**
 * Simplifies the flow involved with managing and executing a batch request queue. Batch requesting is the
 * ability to queue up a series of requests and execute them all in parallel. This allows for faster
 * application performance when a lot of requests are involved.
 *
 * @version 2011.12.02
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFBatchRequest extends CFRuntime
{
	/**
	 * Stores the cURL handles that are to be processed.
	 */
	public $queue;

	/**
	 * Stores the size of the request window.
	 */
	public $limit;

	/**
	 * The proxy to use for connecting.
	 */
	public $proxy = null;

	/**
	 * The helpers to use when connecting.
	 */
	public $helpers = null;

	/**
	 * The active credential set.
	 */
	public $credentials;


	/*%******************************************************************************************%*/
	// CONSTRUCTOR

	/**
	 * Constructs a new instance of this class.
	 *
	 * @param integer $limit (Optional) The size of the request window. Defaults to unlimited.
	 * @return boolean `false` if no valid values are set, otherwise `true`.
	 */
	public function __construct($limit = null)
	{
		$this->queue = array();
		$this->limit = $limit ? $limit : -1;
		$this->credentials = new CFCredential(array());
		return $this;
	}

	/**
	 * Sets the AWS credentials to use for the batch request.
	 *
	 * @param CFCredential $credentials (Required) The credentials to use for signing and making requests.
	 * @return $this A reference to the current instance.
	 */
	public function use_credentials(CFCredential $credentials)
	{
		$this->credentials = $credentials;
		return $this;
	}

	/**
	 * Adds a new cURL handle to the request queue.
	 *
	 * @param resource $handle (Required) A cURL resource to add to the queue.
	 * @return $this A reference to the current instance.
	 */
	public function add($handle)
	{
		$this->queue[] = $handle;
		return $this;
	}

	/**
	 * Executes the batch request queue.
	 *
	 * @param array $opt (DO NOT USE) Enabled for compatibility with the method this overrides, although any values passed will be ignored.
	 * @return array An indexed array of <CFResponse> objects.
	 */
	public function send($opt = null)
	{
		$http = new $this->request_class(null, $this->proxy, null, $this->credentials);

		// Make the request
		$response = $http->send_multi_request($this->queue, array(
			'limit' => $this->limit
		));

		return $response;
	}
}
