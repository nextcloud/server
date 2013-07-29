<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\Common\Client;

use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Signature\SignatureInterface;
use Aws\Common\Waiter\WaiterFactoryInterface;
use Aws\Common\Waiter\WaiterInterface;
use Guzzle\Service\ClientInterface;

/**
 * Interface that all AWS clients implement
 */
interface AwsClientInterface extends ClientInterface
{
    /**
     * Returns the AWS credentials associated with the client
     *
     * @return CredentialsInterface
     */
    public function getCredentials();

    /**
     * Sets the credentials object associated with the client
     *
     * @param CredentialsInterface $credentials Credentials object to use
     *
     * @return self
     */
    public function setCredentials(CredentialsInterface $credentials);

    /**
     * Returns the signature implementation used with the client
     *
     * @return SignatureInterface
     */
    public function getSignature();

    /**
     * Get a list of available regions and region data
     *
     * @return array
     */
    public function getRegions();

    /**
     * Get the name of the region to which the client is configured to send requests
     *
     * @return string
     */
    public function getRegion();

    /**
     * Change the region to which the client is configured to send requests
     *
     * @param string $region Name of the region
     *
     * @return self
     */
    public function setRegion($region);

    /**
     * Get the waiter factory being used by the client
     *
     * @return WaiterFactoryInterface
     */
    public function getWaiterFactory();

    /**
     * Set the waiter factory to use with the client
     *
     * @param WaiterFactoryInterface $waiterFactory Factory used to create waiters
     *
     * @return self
     */
    public function setWaiterFactory(WaiterFactoryInterface $waiterFactory);

    /**
     * Wait until a resource is available or an associated waiter returns true
     *
     * @param string $waiter Name of the waiter
     * @param array  $input  Values used as input for the underlying operation and to control the waiter
     *
     * @return self
     */
    public function waitUntil($waiter, array $input = array());

    /**
     * Get a named waiter object
     *
     * @param string $waiter Name of the waiter
     * @param array  $input  Values used as input for the underlying operation and to control the waiter
     *
     * @return WaiterInterface
     */
    public function getWaiter($waiter, array $input = array());

    /**
     * Get the API version of the client (e.g. 2006-03-01)
     *
     * @return string
     */
    public function getApiVersion();
}
