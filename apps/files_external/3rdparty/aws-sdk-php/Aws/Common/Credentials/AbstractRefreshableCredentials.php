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
 * Abstract decorator to provide a foundation for refreshable credentials
 */
abstract class AbstractRefreshableCredentials extends AbstractCredentialsDecorator
{
    /**
     * {@inheritdoc}
     */
    public function getAccessKeyId()
    {
        if ($this->credentials->isExpired()) {
            $this->refresh();
        }

        return $this->credentials->getAccessKeyId();
    }

    /**
     * {@inheritdoc}
     */
    public function getSecretKey()
    {
        if ($this->credentials->isExpired()) {
            $this->refresh();
        }

        return $this->credentials->getSecretKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityToken()
    {
        if ($this->credentials->isExpired()) {
            $this->refresh();
        }

        return $this->credentials->getSecurityToken();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        if ($this->credentials->isExpired()) {
            $this->refresh();
        }

        return $this->credentials->serialize();
    }

    /**
     * Attempt to get new credentials
     */
    abstract protected function refresh();
}
