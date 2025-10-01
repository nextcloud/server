<?php
namespace Aws\Endpoint\UseDualstackEndpoint;

use Aws\AbstractConfigurationProvider;
use Aws\CacheInterface;
use Aws\ConfigurationProviderInterface;
use Aws\Endpoint\UseDualstackEndpoint\Exception\ConfigurationException;
use GuzzleHttp\Promise;

/**
 * A configuration provider is a function that returns a promise that is
 * fulfilled with a {@see \Aws\Endpoint\UseDualstackEndpoint\onfigurationInterface}
 * or rejected with an {@see \Aws\Endpoint\UseDualstackEndpoint\ConfigurationException}.
 *
 * <code>
 * use Aws\Endpoint\UseDualstackEndpoint\ConfigurationProvider;
 * $provider = ConfigurationProvider::defaultProvider();
 * // Returns a ConfigurationInterface or throws.
 * $config = $provider()->wait();
 * </code>
 *
 * Configuration providers can be composed to create configuration using
 * conditional logic that can create different configurations in different
 * environments. You can compose multiple providers into a single provider using
 * {@see Aws\Endpoint\UseDualstackEndpoint\ConfigurationProvider::chain}. This function
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
    const ENV_USE_DUAL_STACK_ENDPOINT = 'AWS_USE_DUALSTACK_ENDPOINT';
    const INI_USE_DUAL_STACK_ENDPOINT = 'use_dualstack_endpoint';

    public static $cacheKey = 'aws_cached_use_dualstack_endpoint_config';

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
        $region = $config['region'];
        $configProviders = [self::env($region)];
        if (
            !isset($config['use_aws_shared_config_files'])
            || $config['use_aws_shared_config_files'] != false
        ) {
            $configProviders[] = self::ini($region);
        }
        $configProviders[] = self::fallback($region);

        $memo = self::memoize(
            call_user_func_array([ConfigurationProvider::class, 'chain'], $configProviders)
        );

        if (isset($config['use_dual_stack_endpoint'])
            && $config['use_dual_stack_endpoint'] instanceof CacheInterface
        ) {
            return self::cache($memo, $config['use_dual_stack_endpoint'], self::$cacheKey);
        }

        return $memo;
    }

    /**
     * Provider that creates config from environment variables.
     *
     * @return callable
     */
    public static function env($region)
    {
        return function () use ($region) {
            // Use config from environment variables, if available
            $useDualstackEndpoint = getenv(self::ENV_USE_DUAL_STACK_ENDPOINT);
            if (!empty($useDualstackEndpoint)) {
                return Promise\Create::promiseFor(
                    new Configuration($useDualstackEndpoint, $region)
                );
            }

            return self::reject('Could not find environment variable config'
                . ' in ' . self::ENV_USE_DUAL_STACK_ENDPOINT);
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
    public static function ini($region, $profile = null, $filename = null)
    {
        $filename = $filename ?: (self::getDefaultConfigFilename());
        $profile = $profile ?: (getenv(self::ENV_PROFILE) ?: 'default');

        return function () use ($region, $profile, $filename) {
            if (!@is_readable($filename)) {
                return self::reject("Cannot read configuration from $filename");
            }

            // Use INI_SCANNER_NORMAL instead of INI_SCANNER_TYPED for PHP 5.5 compatibility
            $data = \Aws\parse_ini_file($filename, true, INI_SCANNER_NORMAL);
            if ($data === false) {
                return self::reject("Invalid config file: $filename");
            }
            if (!isset($data[$profile])) {
                return self::reject("'$profile' not found in config file");
            }
            if (!isset($data[$profile][self::INI_USE_DUAL_STACK_ENDPOINT])) {
                return self::reject("Required use dualstack endpoint config values
                    not present in INI profile '{$profile}' ({$filename})");
            }

            // INI_SCANNER_NORMAL parses false-y values as an empty string
            if ($data[$profile][self::INI_USE_DUAL_STACK_ENDPOINT] === "") {
                $data[$profile][self::INI_USE_DUAL_STACK_ENDPOINT] = false;
            }

            return Promise\Create::promiseFor(
                new Configuration($data[$profile][self::INI_USE_DUAL_STACK_ENDPOINT], $region)
            );
        };
    }

    /**
     * Fallback config options when other sources are not set.
     *
     * @return callable
     */
    public static function fallback($region)
    {
        return function () use ($region) {
            return Promise\Create::promiseFor(new Configuration(false, $region));
        };
    }
}
