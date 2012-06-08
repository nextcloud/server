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
 * The abstract class that serves as the base class that signer classes extend.
 *
 * @version 2011.11.22
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
abstract class Signer
{
	/**
	 * The endpoint to direct the request to.
	 */
	public $endpoint;

	/**
	 * The operation to execute as a result of this request.
	 */
	public $operation;

	/**
	 * The options to use as part of the payload in the request.
	 */
	public $payload;

	/**
	 * The credentials to use for signing and making requests.
	 */
	public $credentials;


	/**
	 * Constructs a new instance of the implementing class.
	 *
	 * @param string $endpoint (Required) The endpoint to direct the request to.
	 * @param string $operation (Required) The operation to execute as a result of this request.
	 * @param array $payload (Required) The options to use as part of the payload in the request.
	 * @param CFCredential $credentials (Required) The credentials to use for signing and making requests.
	 * @return void
	 */
	public function __construct($endpoint, $operation, $payload, CFCredential $credentials)
	{
		$this->endpoint = $endpoint;
		$this->operation = $operation;
		$this->payload = $payload;
		$this->credentials = $credentials;
	}
}
