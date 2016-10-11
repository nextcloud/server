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
use Guzzle\Common\FromConfigInterface;
use Guzzle\Cache\CacheAdapterInterface;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Common\Collection;

/**
 * Basic implementation of the AWSCredentials interface that allows callers to
 * pass in the AWS access key and secret access in the constructor.
 */
class Credentials implements CredentialsInterface, FromConfigInterface
{
    const ENV_KEY = 'AWS_ACCESS_KEY_ID';
    const ENV_SECRET = 'AWS_SECRET_KEY';
    const ENV_SECRET_ACCESS_KEY = 'AWS_SECRET_ACCESS_KEY';
    const ENV_PROFILE = 'AWS_PROFILE';

    /** @var string AWS Access Key ID */
    protected $key;

    /** @var string AWS Secret Access Key */
    protected $secret;

    /** @var string AWS Security Token */
    protected $token;

    /** @var int Time to die of token */
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
            Options::PROFILE               => null,
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
            $credentials = self::createFromEnvironment($config);
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
            $credentials = self::createCache($credentials, $cache, $cacheKey);
        }

        return $credentials;
    }

    /**
     * Create credentials from the credentials ini file in the HOME directory.
     *
     * @param string|null $profile  Pass a specific profile to use. If no
     *                              profile is specified we will attempt to use
     *                              the value specified in the AWS_PROFILE
     *                              environment variable. If AWS_PROFILE is not
     *                              set, the "default" profile is used.
     * @param string|null $filename Pass a string to specify the location of the
     *                              credentials files. If null is passed, the
     *                              SDK will attempt to find the configuration
     *                              file at in your HOME directory at
     *                              ~/.aws/credentials.
     * @return CredentialsInterface
     * @throws \RuntimeException if the file cannot be found, if the file is
     *                           invalid, or if the profile is invalid.
     */
    public static function fromIni($profile = null, $filename = null)
    {
        if (!$filename) {
            $filename = self::getHomeDir() . '/.aws/credentials';
        }

        if (!$profile) {
            $profile = self::getEnvVar(self::ENV_PROFILE) ?: 'default';
        }

        if (!file_exists($filename) || !($data = parse_ini_file($filename, true))) {
            throw new \RuntimeException("Invalid AWS credentials file: {$filename}.");
        }

        if (empty($data[$profile])) {
            throw new \RuntimeException("Invalid AWS credentials profile {$profile} in {$filename}.");
        }

        return new self(
            $data[$profile]['aws_access_key_id'],
            $data[$profile]['aws_secret_access_key'],
            isset($data[$profile]['aws_security_token'])
                ? $data[$profile]['aws_security_token']
                : null
        );
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

    public function serialize()
    {
        return json_encode(array(
            Options::KEY       => $this->key,
            Options::SECRET    => $this->secret,
            Options::TOKEN     => $this->token,
            Options::TOKEN_TTD => $this->ttd
        ));
    }

    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);
        $this->key    = $data[Options::KEY];
        $this->secret = $data[Options::SECRET];
        $this->token  = $data[Options::TOKEN];
        $this->ttd    = $data[Options::TOKEN_TTD];
    }

    public function getAccessKeyId()
    {
        return $this->key;
    }

    public function getSecretKey()
    {
        return $this->secret;
    }

    public function getSecurityToken()
    {
        return $this->token;
    }

    public function getExpiration()
    {
        return $this->ttd;
    }

    public function isExpired()
    {
        return $this->ttd !== null && time() >= $this->ttd;
    }

    public function setAccessKeyId($key)
    {
        $this->key = $key;

        return $this;
    }

    public function setSecretKey($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    public function setSecurityToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public function setExpiration($timestamp)
    {
        $this->ttd = $timestamp;

        return $this;
    }

    /**
     * When no keys are provided, attempt to create them based on the
     * environment or instance profile credentials.
     *
     * @param array|Collection $config
     *
     * @return CredentialsInterface
     */
    private static function createFromEnvironment($config)
    {
        // Get key and secret from ENV variables
        $envKey = self::getEnvVar(self::ENV_KEY);
        if (!($envSecret = self::getEnvVar(self::ENV_SECRET))) {
            // Use AWS_SECRET_ACCESS_KEY if AWS_SECRET_KEY was not set.
            $envSecret = self::getEnvVar(self::ENV_SECRET_ACCESS_KEY);
        }

        // Use credentials from the environment variables if available
        if ($envKey && $envSecret) {
            return new static($envKey, $envSecret);
        }

        // Use credentials from the ini file in HOME directory if available
        $home = self::getHomeDir();
        if ($home && file_exists("{$home}/.aws/credentials")) {
            return self::fromIni($config[Options::PROFILE], "{$home}/.aws/credentials");
        }

        // Use instance profile credentials (available on EC2 instances)
        return new RefreshableInstanceProfileCredentials(
            new static('', '', '', 1),
            $config[Options::CREDENTIALS_CLIENT]
        );
    }

    private static function createCache(CredentialsInterface $credentials, $cache, $cacheKey)
    {
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
        return new CacheableCredentials($credentials, $cache, $cacheKey);
    }

    private static function getHomeDir()
    {
        // On Linux/Unix-like systems, use the HOME environment variable
        if ($homeDir = self::getEnvVar('HOME')) {
            return $homeDir;
        }

        // Get the HOMEDRIVE and HOMEPATH values for Windows hosts
        $homeDrive = self::getEnvVar('HOMEDRIVE');
        $homePath = self::getEnvVar('HOMEPATH');

        return ($homeDrive && $homePath) ? $homeDrive . $homePath : null;
    }

    /**
     * Fetches the value of an environment variable by checking $_SERVER and getenv().
     *
     * @param string $var Name of the environment variable
     *
     * @return mixed|null
     */
    private static function getEnvVar($var)
    {
        return isset($_SERVER[$var]) ? $_SERVER[$var] : getenv($var);
    }
}
