<?php
namespace Aws\ClientSideMonitoring;

use Aws\AbstractConfigurationProvider;
use Aws\CacheInterface;
use Aws\ClientSideMonitoring\Exception\ConfigurationException;
use Aws\ConfigurationProviderInterface;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * A configuration provider is a function that accepts no arguments and returns
 * a promise that is fulfilled with a {@see \Aws\ClientSideMonitoring\ConfigurationInterface}
 * or rejected with an {@see \Aws\ClientSideMonitoring\Exception\ConfigurationException}.
 *
 * <code>
 * use Aws\ClientSideMonitoring\ConfigurationProvider;
 * $provider = ConfigurationProvider::defaultProvider();
 * // Returns a ConfigurationInterface or throws.
 * $config = $provider()->wait();
 * </code>
 *
 * Configuration providers can be composed to create configuration using
 * conditional logic that can create different configurations in different
 * environments. You can compose multiple providers into a single provider using
 * {@see Aws\ClientSideMonitoring\ConfigurationProvider::chain}. This function
 * accepts providers as variadic arguments and returns a new function that will
 * invoke each provider until a successful configuration is returned.
 *
 * <code>
 * // First try an INI file at this location.
 * $a = ConfigurationProvider::ini(null, '/path/to/file.ini');
 * // Then try an INI file at this location.
 * $b = ConfigurationProvider::ini(null, '/path/to/other-file.ini');
 * // Then try loading from environment variables.
 * $c = ConfigurationProvider::env();
 * // Combine the three providers together.
 * $composed = ConfigurationProvider::chain($a, $b, $c);
 * // Returns a promise that is fulfilled with a configuration or throws.
 * $promise = $composed();
 * // Wait on the configuration to resolve.
 * $config = $promise->wait();
 * </code>
 */
class ConfigurationProvider extends AbstractConfigurationProvider
    implements ConfigurationProviderInterface
{
    const DEFAULT_CLIENT_ID = '';
    const DEFAULT_ENABLED = false;
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 31000;
    const ENV_CLIENT_ID = 'AWS_CSM_CLIENT_ID';
    const ENV_ENABLED = 'AWS_CSM_ENABLED';
    const ENV_HOST = 'AWS_CSM_HOST';
    const ENV_PORT = 'AWS_CSM_PORT';
    const ENV_PROFILE = 'AWS_PROFILE';

    public static $cacheKey = 'aws_cached_csm_config';

    protected static $interfaceClass = ConfigurationInterface::class;
    protected static $exceptionClass = ConfigurationException::class;

    /**
     * Create a default config provider that first checks for environment
     * variables, then checks for a specified profile in the environment-defined
     * config file location (env variable is 'AWS_CONFIG_FILE', file location
     * defaults to ~/.aws/config), then checks for the "default" profile in the
     * environment-defined config file location, and failing those uses a default
     * fallback set of configuration options.
     *
     * This provider is automatically wrapped in a memoize function that caches
     * previously provided config options.
     *
     * @param array $config
     *
     * @return callable
     */
    public static function defaultProvider(array $config = [])
    {
        $configProviders = [self::env()];
        if (
            !isset($config['use_aws_shared_config_files'])
            || $config['use_aws_shared_config_files'] != false
        ) {
            $configProviders[] = self::ini();
        }
        $configProviders[] = self::fallback();

        $memo = self::memoize(
            call_user_func_array('self::chain', $configProviders)
        );

        if (isset($config['csm']) && $config['csm'] instanceof CacheInterface) {
            return self::cache($memo, $config['csm'], self::$cacheKey);
        }

        return $memo;
    }

    /**
     * Provider that creates CSM config from environment variables.
     *
     * @return callable
     */
    public static function env()
    {
        return function () {
            // Use credentials from environment variables, if available
            $enabled = getenv(self::ENV_ENABLED);
            if ($enabled !== false) {
                return Promise\promise_for(
                    new Configuration(
                        $enabled,
                        getenv(self::ENV_HOST) ?: self::DEFAULT_HOST,
                        getenv(self::ENV_PORT) ?: self::DEFAULT_PORT,
                        getenv(self:: ENV_CLIENT_ID) ?: self::DEFAULT_CLIENT_ID
                     )
                );
            }

            return self::reject('Could not find environment variable CSM config'
                . ' in ' . self::ENV_ENABLED. '/' . self::ENV_HOST . '/'
                . self::ENV_PORT . '/' . self::ENV_CLIENT_ID);
        };
    }

    /**
     * Fallback config options when other sources are not set.
     *
     * @return callable
     */
    public static function fallback()
    {
        return function() {
            return Promise\promise_for(
                new Configuration(
                    self::DEFAULT_ENABLED,
                    self::DEFAULT_HOST,
                    self::DEFAULT_PORT,
                    self::DEFAULT_CLIENT_ID
                )
            );
        };
    }

    /**
     * Config provider that creates config using a config file whose location
     * is specified by an environment variable 'AWS_CONFIG_FILE', defaulting to
     * ~/.aws/config if not specified
     *
     * @param string|null $profile  Profile to use. If not specified will use
     *                              the "default" profile.
     * @param string|null $filename If provided, uses a custom filename rather
     *                              than looking in the default directory.
     *
     * @return callable
     */
    public static function ini($profile = null, $filename = null)
    {
        $filename = $filename ?: (self::getDefaultConfigFilename());
        $profile = $profile ?: (getenv(self::ENV_PROFILE) ?: 'aws_csm');

        return function () use ($profile, $filename) {
            if (!is_readable($filename)) {
                return self::reject("Cannot read CSM config from $filename");
            }
            $data = \Aws\parse_ini_file($filename, true);
            if ($data === false) {
                return self::reject("Invalid config file: $filename");
            }
            if (!isset($data[$profile])) {
                return self::reject("'$profile' not found in config file");
            }
            if (!isset($data[$profile]['csm_enabled'])) {
                return self::reject("Required CSM config values not present in 
                    INI profile '{$profile}' ({$filename})");
            }

            // host is optional
            if (empty($data[$profile]['csm_host'])) {
                $data[$profile]['csm_host'] = self::DEFAULT_HOST;
            }

            // port is optional
            if (empty($data[$profile]['csm_port'])) {
                $data[$profile]['csm_port'] = self::DEFAULT_PORT;
            }

            // client_id is optional
            if (empty($data[$profile]['csm_client_id'])) {
                $data[$profile]['csm_client_id'] = self::DEFAULT_CLIENT_ID;
            }

            return Promise\promise_for(
                new Configuration(
                    $data[$profile]['csm_enabled'],
                    $data[$profile]['csm_host'],
                    $data[$profile]['csm_port'],
                    $data[$profile]['csm_client_id']
                )
            );
        };
    }

    /**
     * Unwraps a configuration object in whatever valid form it is in,
     * always returning a ConfigurationInterface object.
     *
     * @param  mixed $config
     * @return ConfigurationInterface
     * @throws \InvalidArgumentException
     */
    public static function unwrap($config)
    {
        if (is_callable($config)) {
            $config = $config();
        }
        if ($config instanceof PromiseInterface) {
            $config = $config->wait();
        }
        if ($config instanceof ConfigurationInterface) {
            return $config;
        } elseif (is_array($config) && isset($config['enabled'])) {
            $client_id = isset($config['client_id']) ? $config['client_id']
                : self::DEFAULT_CLIENT_ID;
            $host = isset($config['host']) ? $config['host']
                : self::DEFAULT_HOST;
            $port = isset($config['port']) ? $config['port']
                : self::DEFAULT_PORT;
            return new Configuration($config['enabled'], $host, $port, $client_id);
        }

        throw new \InvalidArgumentException('Not a valid CSM configuration '
            . 'argument.');
    }
}