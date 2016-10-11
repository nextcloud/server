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

namespace Aws\Common\Credentials;

/**
 * Provides access to the AWS credentials used for accessing AWS services: AWS
 * access key ID, secret access key, and security token. These credentials are
 * used to securely sign requests to AWS services.
 */
interface CredentialsInterface extends \Serializable
{
    /**
     * Returns the AWS access key ID for this credentials object.
     *
     * @return string
     */
    public function getAccessKeyId();

    /**
     * Returns the AWS secret access key for this credentials object.
     *
     * @return string
     */
    public function getSecretKey();

    /**
     * Get the associated security token if available
     *
     * @return string|null
     */
    public function getSecurityToken();

    /**
     * Get the UNIX timestamp in which the credentials will expire
     *
     * @return int|null
     */
    public function getExpiration();

    /**
     * Set the AWS access key ID for this credentials object.
     *
     * @param string $key AWS access key ID
     *
     * @return self
     */
    public function setAccessKeyId($key);

    /**
     * Set the AWS secret access key for this credentials object.
     *
     * @param string $secret AWS secret access key
     *
     * @return CredentialsInterface
     */
    public function setSecretKey($secret);

    /**
     * Set the security token to use with this credentials object
     *
     * @param string $token Security token
     *
     * @return self
     */
    public function setSecurityToken($token);

    /**
     * Set the UNIX timestamp in which the credentials will expire
     *
     * @param int $timestamp UNIX timestamp expiration
     *
     * @return self
     */
    public function setExpiration($timestamp);

    /**
     * Check if the credentials are expired
     *
     * @return bool
     */
    public function isExpired();
}
