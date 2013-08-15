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
 * Abstract credentials decorator
 */
class AbstractCredentialsDecorator implements CredentialsInterface
{
    /**
     * @var CredentialsInterface Wrapped credentials object
     */
    protected $credentials;

    /**
     * Constructs a new BasicAWSCredentials object, with the specified AWS
     * access key and AWS secret key
     *
     * @param CredentialsInterface $credentials
     */
    public function __construct(CredentialsInterface $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return $this->credentials->serialize();
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->credentials = new Credentials('', '');
        $this->credentials->unserialize($serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessKeyId()
    {
        return $this->credentials->getAccessKeyId();
    }

    /**
     * {@inheritdoc}
     */
    public function getSecretKey()
    {
        return $this->credentials->getSecretKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityToken()
    {
        return $this->credentials->getSecurityToken();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiration()
    {
        return $this->credentials->getExpiration();
    }

    /**
     * {@inheritdoc}
     */
    public function isExpired()
    {
        return $this->credentials->isExpired();
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessKeyId($key)
    {
        $this->credentials->setAccessKeyId($key);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSecretKey($secret)
    {
        $this->credentials->setSecretKey($secret);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSecurityToken($token)
    {
        $this->credentials->setSecurityToken($token);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setExpiration($timestamp)
    {
        $this->credentials->setExpiration($timestamp);

        return $this;
    }
}
