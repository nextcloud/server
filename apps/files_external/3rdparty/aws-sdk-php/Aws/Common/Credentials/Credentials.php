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

use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\RequiredExtensionNotLoadedException;
use Aws\Common\Exception\RuntimeException;
use Guzzle\Http\ClientInterface;
use Guzzle\Common\FromConfigInterface;
use Guzzle\Cache\CacheAdapterInterface;
use Guzzle\Cache\DoctrineCacheAdapter;

/**
 * Basic implementation of the AWSCredentials interface that allows callers to
 * pass in the AWS access key and secret access in the constructor.
 */
class Credentials implements CredentialsInterface, FromConfigInterface
{
    const ENV_KEY = 'AWS_ACCESS_KEY_ID';
    const ENV_SECRET = 'AWS_SECRET_KEY';

    /**
     * @var string AWS Access key ID
     */
    protected $key;

    /**
     * @var string AWS Secret access key
     */
    protected $secret;

    /**
     * @var string Security token
     */
    protected $token;

    /**
     * @var int Time to die of token
     */
    protected $ttd;

    /**
     * Get the available keys for the factory method
     *
     * @return array
     */
    public static function getConfigDefaults()
    {
        return array(
            Options::KEY                   => null,
            Options::SECRET                => null,
            Options::TOKEN                 => null,
            Options::TOKEN_TTD             => null,
            Options::CREDENTIALS_CACHE     => null,
            Options::CREDENTIALS_CACHE_KEY => null,
            Options::CREDENTIALS_CLIENT    => null
        );
    }

    /**
     * Factory method for creating new credentials.  This factory method will
     * create the appropriate credentials object with appropriate decorators
     * based on the passed configuration options.
     *
     * @param array $config Options to use when instantiating the credentials
     *
     * @return CredentialsInterface
     * @throws InvalidArgumentException If the caching options are invalid
     * @throws RuntimeException         If using the default cache and APC is disabled
     */
    public static function factory($config = array())
    {
        // Add default key values
        foreach (self::getConfigDefaults() as $key => $value) {
            if (!isset($config[$key])) {
                $config[$key] = $value;
            }
        }

        // Start tracking the cache key
        $cacheKey = $config[Options::CREDENTIALS_CACHE_KEY];

        // Create the credentials object
        if (!$config[Options::KEY] || !$config[Options::SECRET]) {
            // No keys were provided, so attempt to retrieve some from the environment
            $envKey = isset($_SERVER[self::ENV_KEY]) ? $_SERVER[self::ENV_KEY] : getenv(self::ENV_KEY);
            $envSecret = isset($_SERVER[self::ENV_SECRET]) ? $_SERVER[self::ENV_SECRET] : getenv(self::ENV_SECRET);
            if ($envKey && $envSecret) {
                // Use credentials set in the environment variables
                $credentials = new static($envKey, $envSecret);
            } else {
                // Use instance profile credentials (available on EC2 instances)
                $credentials = new RefreshableInstanceProfileCredentials(
                    new static('', '', '', 1),
                    $config[Options::CREDENTIALS_CLIENT]
                );
            }
            // If no cache key was set, use the crc32 hostname of the server
            $cacheKey = $cacheKey ?: 'credentials_' . crc32(gethostname());
        } else {
            // Instantiate using short or long term credentials
            $credentials = new static(
                $config[Options::KEY],
                $config[Options::SECRET],
                $config[Options::TOKEN],
                $config[Options::TOKEN_TTD]
            );
            // If no cache key was set, use the access key ID
            $cacheKey = $cacheKey ?: 'credentials_' . $config[Options::KEY];
        }

        // Check if the credentials are refreshable, and if so, configure caching
        $cache = $config[Options::CREDENTIALS_CACHE];
        if ($cacheKey && $cache) {
            if ($cache === 'true' || $cache === true) {
                // If no cache adapter was provided, then create one for the user
                // @codeCoverageIgnoreStart
                if (!extension_loaded('apc')) {
                    throw new RequiredExtensionNotLoadedException('PHP has not been compiled with APC. Unable to cache '
                        . 'the credentials.');
                } elseif (!class_exists('Doctrine\Common\Cache\ApcCache')) {
                    throw new RuntimeException(
                        'Cannot set ' . Options::CREDENTIALS_CACHE . ' to true because the Doctrine cache component is '
                        . 'not installed. Either install doctrine/cache or pass in an instantiated '
                        . 'Guzzle\Cache\CacheAdapterInterface object'
                    );
                }
                // @codeCoverageIgnoreEnd
                $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ApcCache());
            } elseif (!($cache instanceof CacheAdapterInterface)) {
                throw new InvalidArgumentException('Unable to utilize caching with the specified options');
            }
            // Decorate the credentials with a cache
            $credentials = new CacheableCredentials($credentials, $cache, $cacheKey);
        }

        return $credentials;
    }

    /**
     * Constructs a new BasicAWSCredentials object, with the specified AWS
     * access key and AWS secret key
     *
     * @param string $accessKeyId     AWS access key ID
     * @param string $secretAccessKey AWS secret access key
     * @param string $token           Security token to use
     * @param int    $expiration      UNIX timestamp for when credentials expire
     */
    public function __construct($accessKeyId, $secretAccessKey, $token = null, $expiration = null)
    {
        $this->key = trim($accessKeyId);
        $this->secret = trim($secretAccessKey);
        $this->token = $token;
        $this->ttd = $expiration;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return json_encode(array(
            Options::KEY       => $this->key,
            Options::SECRET    => $this->secret,
            Options::TOKEN     => $this->token,
            Options::TOKEN_TTD => $this->ttd
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);
        $this->key    = $data[Options::KEY];
        $this->secret = $data[Options::SECRET];
        $this->token  = $data[Options::TOKEN];
        $this->ttd    = $data[Options::TOKEN_TTD];
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessKeyId()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecretKey()
    {
        return $this->secret;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityToken()
    {
        return $this->token;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiration()
    {
        return $this->ttd;
    }

    /**
     * {@inheritdoc}
     */
    public function isExpired()
    {
        return $this->ttd !== null && time() >= $this->ttd;
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessKeyId($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSecretKey($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSecurityToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setExpiration($timestamp)
    {
        $this->ttd = $timestamp;

        return $this;
    }
}
