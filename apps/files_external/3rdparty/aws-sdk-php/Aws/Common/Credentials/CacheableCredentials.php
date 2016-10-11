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

use Guzzle\Cache\CacheAdapterInterface;

/**
 * Credentials decorator used to implement caching credentials
 */
class CacheableCredentials extends AbstractRefreshableCredentials
{
    /**
     * @var CacheAdapterInterface Cache adapter used to store credentials
     */
    protected $cache;

    /**
     * @var string Cache key used to store the credentials
     */
    protected $cacheKey;

    /**
     * CacheableCredentials is a decorator that decorates other credentials
     *
     * @param CredentialsInterface  $credentials Credentials to adapt
     * @param CacheAdapterInterface $cache       Cache to use to store credentials
     * @param string                $cacheKey    Cache key of the credentials
     */
    public function __construct(CredentialsInterface $credentials, CacheAdapterInterface $cache, $cacheKey)
    {
        $this->credentials = $credentials;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * Attempt to get new credentials from cache or from the adapted object
     */
    protected function refresh()
    {
        if (!$cache = $this->cache->fetch($this->cacheKey)) {
            // The credentials were not found, so try again and cache if new
            $this->credentials->getAccessKeyId();
            if (!$this->credentials->isExpired()) {
                // The credentials were updated, so cache them
                $this->cache->save($this->cacheKey, $this->credentials, $this->credentials->getExpiration() - time());
            }
        } else {
            // The credentials were found in cache, so update the adapter object
            // if the cached credentials are not expired
            if (!$cache->isExpired()) {
                $this->credentials->setAccessKeyId($cache->getAccessKeyId());
                $this->credentials->setSecretKey($cache->getSecretKey());
                $this->credentials->setSecurityToken($cache->getSecurityToken());
                $this->credentials->setExpiration($cache->getExpiration());
            }
        }
    }
}
