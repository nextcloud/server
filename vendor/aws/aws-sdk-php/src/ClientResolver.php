<?php
namespace Aws;

use Aws\Api\ApiProvider;
use Aws\Api\Service;
use Aws\Api\Validator;
use Aws\Auth\AuthResolver;
use Aws\Auth\AuthSchemeResolver;
use Aws\Auth\AuthSchemeResolverInterface;
use Aws\ClientSideMonitoring\ApiCallAttemptMonitoringMiddleware;
use Aws\ClientSideMonitoring\ApiCallMonitoringMiddleware;
use Aws\ClientSideMonitoring\Configuration;
use Aws\Configuration\ConfigurationResolver;
use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Credentials\CredentialsInterface;
use Aws\DefaultsMode\ConfigurationInterface as ConfigModeInterface;
use Aws\DefaultsMode\ConfigurationProvider as ConfigModeProvider;
use Aws\Endpoint\EndpointProvider;
use Aws\Endpoint\PartitionEndpointProvider;
use Aws\Endpoint\UseDualstackEndpoint\Configuration as UseDualStackEndpointConfiguration;
use Aws\Endpoint\UseDualstackEndpoint\ConfigurationInterface as UseDualStackEndpointConfigurationInterface;
use Aws\Endpoint\UseDualstackEndpoint\ConfigurationProvider as UseDualStackConfigProvider;
use Aws\Endpoint\UseFipsEndpoint\Configuration as UseFipsEndpointConfiguration;
use Aws\Endpoint\UseFipsEndpoint\ConfigurationInterface as UseFipsEndpointConfigurationInterface;
use Aws\Endpoint\UseFipsEndpoint\ConfigurationProvider as UseFipsConfigProvider;
use Aws\EndpointDiscovery\ConfigurationInterface;
use Aws\EndpointDiscovery\ConfigurationProvider;
use Aws\EndpointV2\EndpointDefinitionProvider;
use Aws\Exception\AwsException;
use Aws\Exception\InvalidRegionException;
use Aws\Retry\ConfigurationInterface as RetryConfigInterface;
use Aws\Retry\ConfigurationProvider as RetryConfigProvider;
use Aws\Signature\SignatureProvider;
use Aws\Token\Token;
use Aws\Token\TokenInterface;
use Aws\Token\TokenProvider;
use GuzzleHttp\Promise\PromiseInterface;
use InvalidArgumentException as IAE;
use Psr\Http\Message\RequestInterface;

/**
 * @internal Resolves a hash of client arguments to construct a client.
 */
class ClientResolver
{
    /** @var array */
    private $argDefinitions;

    /**
     * When using this option as default please make sure that, your config
     * has at least one data type defined in `valid` otherwise it will be
     * defaulted to `string`. Also, the default value will be the falsy value
     * based on the resolved data type. For example, the default for `string`
     * will be `''` and for bool will be `false`.
     *
     * @var string
     */
    const DEFAULT_FROM_ENV_INI = [
        __CLASS__,
        '_resolve_from_env_ini'
    ];

    /** @var array Map of types to a corresponding function */
    private static $typeMap = [
        'resource' => 'is_resource',
        'callable' => 'is_callable',
        'int'      => 'is_int',
        'bool'     => 'is_bool',
        'boolean'  => 'is_bool',
        'string'   => 'is_string',
        'object'   => 'is_object',
        'array'    => 'is_array',
    ];

    private static $defaultArgs = [
        'service' => [
            'type'     => 'value',
            'valid'    => ['string'],
            'doc'      => 'Name of the service to utilize. This value will be supplied by default when using one of the SDK clients (e.g., Aws\\S3\\S3Client).',
            'required' => true,
            'internal' => true
        ],
        'exception_class' => [
            'type'     => 'value',
            'valid'    => ['string'],
            'doc'      => 'Exception class to create when an error occurs.',
            'default'  => AwsException::class,
            'internal' => true
        ],
        'scheme' => [
            'type'     => 'value',
            'valid'    => ['string'],
            'default'  => 'https',
            'doc'      => 'URI scheme to use when connecting connect. The SDK will utilize "https" endpoints (i.e., utilize SSL/TLS connections) by default. You can attempt to connect to a service over an unencrypted "http" endpoint by setting ``scheme`` to "http".',
        ],
        'disable_host_prefix_injection' => [
            'type'      => 'value',
            'valid'     => ['bool'],
            'doc'       => 'Set to true to disable host prefix injection logic for services that use it. This disables the entire prefix injection, including the portions supplied by user-defined parameters. Setting this flag will have no effect on services that do not use host prefix injection.',
            'default'   => false,
        ],
        'ignore_configured_endpoint_urls' => [
            'type'      => 'value',
            'valid'     => ['bool'],
            'doc'       => 'Set to true to disable endpoint urls configured using `AWS_ENDPOINT_URL` and `endpoint_url` shared config option.',
            'fn'        => [__CLASS__, '_apply_ignore_configured_endpoint_urls'],
            'default'   => self::DEFAULT_FROM_ENV_INI,
        ],
        'endpoint' => [
            'type'  => 'value',
            'valid' => ['string'],
            'doc'   => 'The full URI of the webservice. This is only required when connecting to a custom endpoint (e.g., a local version of S3).',
            'fn'    => [__CLASS__, '_apply_endpoint'],
            'default'   => [__CLASS__, '_default_endpoint']
        ],
        'region' => [
            'type'     => 'value',
            'valid'    => ['string'],
            'doc'      => 'Region to connect to. See http://docs.aws.amazon.com/general/latest/gr/rande.html for a list of available regions.',
            'fn'       => [__CLASS__, '_apply_region'],
            'default'  => self::DEFAULT_FROM_ENV_INI
        ],
        'version' => [
            'type'     => 'value',
            'valid'    => ['string'],
            'doc'      => 'The version of the webservice to utilize (e.g., 2006-03-01).',
            'default' => 'latest',
        ],
        'signature_provider' => [
            'type'    => 'value',
            'valid'   => ['callable'],
            'doc'     => 'A callable that accepts a signature version name (e.g., "v4"), a service name, and region, and  returns a SignatureInterface object or null. This provider is used to create signers utilized by the client. See Aws\\Signature\\SignatureProvider for a list of built-in providers',
            'default' => [__CLASS__, '_default_signature_provider'],
        ],
        'api_provider' => [
            'type'     => 'value',
            'valid'    => ['callable'],
            'doc'      => 'An optional PHP callable that accepts a type, service, and version argument, and returns an array of corresponding configuration data. The type value can be one of api, waiter, or paginator.',
            'fn'       => [__CLASS__, '_apply_api_provider'],
            'default'  => [ApiProvider::class, 'defaultProvider'],
        ],
        'configuration_mode' => [
            'type'    => 'value',
            'valid'   => [ConfigModeInterface::class, CacheInterface::class, 'string', 'closure'],
            'doc'     => "Sets the default configuration mode. Otherwise provide an instance of Aws\DefaultsMode\ConfigurationInterface, an instance of  Aws\CacheInterface, or a string containing a valid mode",
            'fn'      => [__CLASS__, '_apply_defaults'],
            'default' => [ConfigModeProvider::class, 'defaultProvider']
        ],
        'use_fips_endpoint' => [
            'type'      => 'value',
            'valid'     => ['bool', UseFipsEndpointConfiguration::class, CacheInterface::class, 'callable'],
            'doc'       => 'Set to true to enable the use of FIPS pseudo regions',
            'fn'        => [__CLASS__, '_apply_use_fips_endpoint'],
            'default'   => [__CLASS__, '_default_use_fips_endpoint'],
        ],
        'use_dual_stack_endpoint' => [
            'type'      => 'value',
            'valid'     => ['bool', UseDualStackEndpointConfiguration::class, CacheInterface::class, 'callable'],
            'doc'       => 'Set to true to enable the use of dual-stack endpoints',
            'fn'        => [__CLASS__, '_apply_use_dual_stack_endpoint'],
            'default'   => [__CLASS__, '_default_use_dual_stack_endpoint'],
        ],
        'endpoint_provider' => [
            'type'     => 'value',
            'valid'    => ['callable', EndpointV2\EndpointProviderV2::class],
            'fn'       => [__CLASS__, '_apply_endpoint_provider'],
            'doc'      => 'An optional PHP callable that accepts a hash of options including a "service" and "region" key and returns NULL or a hash of endpoint data, of which the "endpoint" key is required. See Aws\\Endpoint\\EndpointProvider for a list of built-in providers.',
            'default'  => [__CLASS__, '_default_endpoint_provider'],
        ],
        'serializer' => [
            'default'   => [__CLASS__, '_default_serializer'],
            'fn'        => [__CLASS__, '_apply_serializer'],
            'internal'  => true,
            'type'      => 'value',
            'valid'     => ['callable'],
        ],
        'signature_version' => [
            'type'    => 'config',
            'valid'   => ['string'],
            'doc'     => 'A string representing a custom signature version to use with a service (e.g., v4). Note that per/operation signature version MAY override this requested signature version.',
            'default' => [__CLASS__, '_default_signature_version'],
        ],
        'signing_name' => [
            'type'    => 'config',
            'valid'   => ['string'],
            'doc'     => 'A string representing a custom service name to be used when calculating a request signature.',
            'default' => [__CLASS__, '_default_signing_name'],
        ],
        'signing_region' => [
            'type'    => 'config',
            'valid'   => ['string'],
            'doc'     => 'A string representing a custom region name to be used when calculating a request signature.',
            'default' => [__CLASS__, '_default_signing_region'],
        ],
        'profile' => [
            'type'  => 'config',
            'valid' => ['string'],
            'doc'   => 'Allows you to specify which profile to use when credentials are created from the AWS credentials file in your HOME directory. This setting overrides the AWS_PROFILE environment variable. Note: Specifying "profile" will cause the "credentials" and "use_aws_shared_config_files" keys to be ignored.',
            'fn'    => [__CLASS__, '_apply_profile'],
        ],
        'credentials' => [
            'type'    => 'value',
            'valid'   => [CredentialsInterface::class, CacheInterface::class, 'array', 'bool', 'callable'],
            'doc'     => 'Specifies the credentials used to sign requests. Provide an Aws\Credentials\CredentialsInterface object, an associative array of "key", "secret", and an optional "token" key, `false` to use null credentials, or a callable credentials provider used to create credentials or return null. See Aws\\Credentials\\CredentialProvider for a list of built-in credentials providers. If no credentials are provided, the SDK will attempt to load them from the environment.',
            'fn'      => [__CLASS__, '_apply_credentials'],
            'default' => [__CLASS__, '_default_credential_provider'],
        ],
        'token' => [
            'type'    => 'value',
            'valid'   => [TokenInterface::class, CacheInterface::class, 'array', 'bool', 'callable'],
            'doc'     => 'Specifies the token used to authorize requests. Provide an Aws\Token\TokenInterface object, an associative array of "token", and an optional "expiration" key, `false` to use a null token, or a callable token provider used to fetch a token or return null. See Aws\\Token\\TokenProvider for a list of built-in credentials providers. If no token is provided, the SDK will attempt to load one from the environment.',
            'fn'      => [__CLASS__, '_apply_token'],
            'default' => [__CLASS__, '_default_token_provider'],
        ],
        'auth_scheme_resolver' => [
            'type'    => 'value',
            'valid'   => [AuthSchemeResolverInterface::class],
            'doc'     => 'An instance of Aws\Auth\AuthSchemeResolverInterface which selects a modeled auth scheme and returns a signature version',
            'default' => [__CLASS__, '_default_auth_scheme_resolver'],
        ],
        'endpoint_discovery' => [
            'type'     => 'value',
            'valid'    => [ConfigurationInterface::class, CacheInterface::class, 'array', 'callable'],
            'doc'      => 'Specifies settings for endpoint discovery. Provide an instance of Aws\EndpointDiscovery\ConfigurationInterface, an instance Aws\CacheInterface, a callable that provides a promise for a Configuration object, or an associative array with the following keys: enabled: (bool) Set to true to enable endpoint discovery, false to explicitly disable it. Defaults to false; cache_limit: (int) The maximum number of keys in the endpoints cache. Defaults to 1000.',
            'fn'       => [__CLASS__, '_apply_endpoint_discovery'],
            'default'  => [__CLASS__, '_default_endpoint_discovery_provider']
        ],
        'stats' => [
            'type'  => 'value',
            'valid' => ['bool', 'array'],
            'default' => false,
            'doc'   => 'Set to true to gather transfer statistics on requests sent. Alternatively, you can provide an associative array with the following keys: retries: (bool) Set to false to disable reporting on retries attempted; http: (bool) Set to true to enable collecting statistics from lower level HTTP adapters (e.g., values returned in GuzzleHttp\TransferStats). HTTP handlers must support an http_stats_receiver option for this to have an effect; timer: (bool) Set to true to enable a command timer that reports the total wall clock time spent on an operation in seconds.',
            'fn'    => [__CLASS__, '_apply_stats'],
        ],
        'retries' => [
            'type'    => 'value',
            'valid'   => ['int', RetryConfigInterface::class, CacheInterface::class, 'callable', 'array'],
            'doc'     => "Configures the retry mode and maximum number of allowed retries for a client (pass 0 to disable retries). Provide an integer for 'legacy' mode with the specified number of retries. Otherwise provide an instance of Aws\Retry\ConfigurationInterface, an instance of  Aws\CacheInterface, a callable function, or an array with the following keys: mode: (string) Set to 'legacy', 'standard' (uses retry quota management), or 'adapative' (an experimental mode that adds client-side rate limiting to standard mode); max_attempts: (int) The maximum number of attempts for a given request. ",
            'fn'      => [__CLASS__, '_apply_retries'],
            'default' => [RetryConfigProvider::class, 'defaultProvider']
        ],
        'validate' => [
            'type'    => 'value',
            'valid'   => ['bool', 'array'],
            'default' => true,
            'doc'     => 'Set to false to disable client-side parameter validation. Set to true to utilize default validation constraints. Set to an associative array of validation options to enable specific validation constraints.',
            'fn'      => [__CLASS__, '_apply_validate'],
        ],
        'debug' => [
            'type'  => 'value',
            'valid' => ['bool', 'array'],
            'doc'   => 'Set to true to display debug information when sending requests. Alternatively, you can provide an associative array with the following keys: logfn: (callable) Function that is invoked with log messages; stream_size: (int) When the size of a stream is greater than this number, the stream data will not be logged (set to "0" to not log any stream data); scrub_auth: (bool) Set to false to disable the scrubbing of auth data from the logged messages; http: (bool) Set to false to disable the "debug" feature of lower level HTTP adapters (e.g., verbose curl output).',
            'fn'    => [__CLASS__, '_apply_debug'],
        ],
        'disable_request_compression' => [
            'type'      => 'value',
            'valid'     => ['bool', 'callable'],
            'doc'       => 'Set to true to disable request compression for supported operations',
            'fn'        => [__CLASS__, '_apply_disable_request_compression'],
            'default'   => self::DEFAULT_FROM_ENV_INI,
        ],
        'request_min_compression_size_bytes' => [
            'type'      => 'value',
            'valid'     => ['int', 'callable'],
            'doc'       => 'Set to a value between between 0 and 10485760 bytes, inclusive. This value will be ignored if `disable_request_compression` is set to `true`',
            'fn'        => [__CLASS__, '_apply_min_compression_size'],
            'default'   => [__CLASS__, '_default_min_compression_size'],
        ],
        'csm' => [
            'type'     => 'value',
            'valid'    => [\Aws\ClientSideMonitoring\ConfigurationInterface::class, 'callable', 'array', 'bool'],
            'doc'      => 'CSM options for the client. Provides a callable wrapping a promise, a boolean "false", an instance of ConfigurationInterface, or an associative array of "enabled", "host", "port", and "client_id".',
            'fn'       => [__CLASS__, '_apply_csm'],
            'default'  => [\Aws\ClientSideMonitoring\ConfigurationProvider::class, 'defaultProvider']
        ],
        'http' => [
            'type'    => 'value',
            'valid'   => ['array'],
            'default' => [],
            'doc'     => 'Set to an array of SDK request options to apply to each request (e.g., proxy, verify, etc.).',
        ],
        'http_handler' => [
            'type'    => 'value',
            'valid'   => ['callable'],
            'doc'     => 'An HTTP handler is a function that accepts a PSR-7 request object and returns a promise that is fulfilled with a PSR-7 response object or rejected with an array of exception data. NOTE: This option supersedes any provided "handler" option.',
            'fn'      => [__CLASS__, '_apply_http_handler']
        ],
        'handler' => [
            'type'     => 'value',
            'valid'    => ['callable'],
            'doc'      => 'A handler that accepts a command object, request object and returns a promise that is fulfilled with an Aws\ResultInterface object or rejected with an Aws\Exception\AwsException. A handler does not accept a next handler as it is terminal and expected to fulfill a command. If no handler is provided, a default Guzzle handler will be utilized.',
            'fn'       => [__CLASS__, '_apply_handler'],
            'default'  => [__CLASS__, '_default_handler']
        ],
        'app_id' => [
            'type' => 'value',
            'valid' => ['string'],
            'doc' => 'app_id(AppId) is an optional application specific identifier that can be set. 
             When set it will be appended to the User-Agent header of every request in the form of App/{AppId}. 
             This value is also sourced from environment variable AWS_SDK_UA_APP_ID or the shared config profile attribute sdk_ua_app_id.',
            'fn' => [__CLASS__, '_apply_app_id'],
            'default' => [__CLASS__, '_default_app_id']
        ],
        'ua_append' => [
            'type'     => 'value',
            'valid'    => ['string', 'array'],
            'doc'      => 'Provide a string or array of strings to send in the User-Agent header.',
            'fn'       => [__CLASS__, '_apply_user_agent'],
            'default'  => [],
        ],
        'idempotency_auto_fill' => [
            'type'      => 'value',
            'valid'     => ['bool', 'callable'],
            'doc'       => 'Set to false to disable SDK to populate parameters that enabled \'idempotencyToken\' trait with a random UUID v4 value on your behalf. Using default value \'true\' still allows parameter value to be overwritten when provided. Note: auto-fill only works when cryptographically secure random bytes generator functions(random_bytes, openssl_random_pseudo_bytes or mcrypt_create_iv) can be found. You may also provide a callable source of random bytes.',
            'default'   => true,
            'fn'        => [__CLASS__, '_apply_idempotency_auto_fill']
        ],
        'use_aws_shared_config_files' => [
            'type'      => 'value',
            'valid'     => ['bool'],
            'doc'       => 'Set to false to disable checking for shared aws config files usually located in \'~/.aws/config\' and \'~/.aws/credentials\'.  This will be ignored if you set the \'profile\' setting.',
            'default'   => true,
        ],
        'suppress_php_deprecation_warning' => [
            'type'      => 'value',
            'valid'     => ['bool'],
            'doc' => 'Set to true to suppress PHP runtime deprecation warnings. The current deprecation campaign is PHP versions 8.0.x and below, taking effect on 1/13/2025.',
            'default' => false,
            'fn' => [__CLASS__, '_apply_suppress_php_deprecation_warning']
        ],
        'account_id_endpoint_mode' => [
            'type'      => 'value',
            'valid'     => ['string'],
            'doc'       => 'Decides whether account_id must a be a required resolved credentials property. If this configuration is set to disabled, then account_id is not required. If set to preferred a warning will be logged when account_id is not resolved, and when set to required an exception will be thrown if account_id is not resolved.',
            'default'  => [__CLASS__, '_default_account_id_endpoint_mode'],
            'fn'       => [__CLASS__, '_apply_account_id_endpoint_mode']
        ],
        'sigv4a_signing_region_set' => [
            'type' => 'value',
            'valid' => ['string', 'array'],
            'doc' => 'A comma-delimited list of supported regions sent in sigv4a requests.',
            'fn' => [__CLASS__, '_apply_sigv4a_signing_region_set'],
            'default' => self::DEFAULT_FROM_ENV_INI
        ]
    ];

    /**
     * Gets an array of default client arguments, each argument containing a
     * hash of the following:
     *
     * - type: (string, required) option type described as follows:
     *   - value: The default option type.
     *   - config: The provided value is made available in the client's
     *     getConfig() method.
     * - valid: (array, required) Valid PHP types or class names. Note: null
     *   is not an allowed type.
     * - required: (bool, callable) Whether or not the argument is required.
     *   Provide a function that accepts an array of arguments and returns a
     *   string to provide a custom error message.
     * - default: (mixed) The default value of the argument if not provided. If
     *   a function is provided, then it will be invoked to provide a default
     *   value. The function is provided the array of options and is expected
     *   to return the default value of the option. The default value can be a
     *   closure and can not be a callable string that is not  part of the
     *   defaultArgs array.
     * - doc: (string) The argument documentation string.
     * - fn: (callable) Function used to apply the argument. The function
     *   accepts the provided value, array of arguments by reference, and an
     *   event emitter.
     *
     * Note: Order is honored and important when applying arguments.
     *
     * @return array
     */
    public static function getDefaultArguments()
    {
        return self::$defaultArgs;
    }

    /**
     * @param array $argDefinitions Client arguments.
     */
    public function __construct(array $argDefinitions)
    {
        $this->argDefinitions = $argDefinitions;
    }

    /**
     * Resolves client configuration options and attached event listeners.
     * Check for missing keys in passed arguments
     *
     * @param array       $args Provided constructor arguments.
     * @param HandlerList $list Handler list to augment.
     *
     * @return array Returns the array of provided options.
     * @throws \InvalidArgumentException
     * @see Aws\AwsClient::__construct for a list of available options.
     */
    public function resolve(array $args, HandlerList $list)
    {
        $args['config'] = [];
        foreach ($this->argDefinitions as $key => $a) {
            // Add defaults, validate required values, and skip if not set.
            if (!isset($args[$key])) {
                if (isset($a['default'])) {
                    // Merge defaults in when not present.
                    if (is_callable($a['default'])
                        && (
                            is_array($a['default'])
                            || $a['default'] instanceof \Closure
                        )
                    ) {
                        if ($a['default'] === self::DEFAULT_FROM_ENV_INI) {
                            $args[$key] = $a['default'](
                                $key,
                                $a['valid'][0] ?? 'string',
                                $args
                            );
                        } else {
                            $args[$key] = $a['default']($args);
                        }
                    } else {
                        $args[$key] = $a['default'];
                    }
                } elseif (empty($a['required'])) {
                    continue;
                } else {
                    $this->throwRequired($args);
                }
            }

            // Validate the types against the provided value.
            foreach ($a['valid'] as $check) {
                if (isset(self::$typeMap[$check])) {
                    $fn = self::$typeMap[$check];
                    if ($fn($args[$key])) {
                        goto is_valid;
                    }
                } elseif ($args[$key] instanceof $check) {
                    goto is_valid;
                }
            }

            $this->invalidType($key, $args[$key]);

            // Apply the value
            is_valid:
            if (isset($a['fn'])) {
                $a['fn']($args[$key], $args, $list);
            }

            if ($a['type'] === 'config') {
                $args['config'][$key] = $args[$key];
            }
        }
        $this->_apply_client_context_params($args);

        return $args;
    }

    /**
     * Creates a verbose error message for an invalid argument.
     *
     * @param string $name        Name of the argument that is missing.
     * @param array  $args        Provided arguments
     * @param bool   $useRequired Set to true to show the required fn text if
     *                            available instead of the documentation.
     * @return string
     */
    private function getArgMessage($name, $args = [], $useRequired = false)
    {
        $arg = $this->argDefinitions[$name];
        $msg = '';
        $modifiers = [];
        if (isset($arg['valid'])) {
            $modifiers[] = implode('|', $arg['valid']);
        }
        if (isset($arg['choice'])) {
            $modifiers[] = 'One of ' . implode(', ', $arg['choice']);
        }
        if ($modifiers) {
            $msg .= '(' . implode('; ', $modifiers) . ')';
        }
        $msg = wordwrap("{$name}: {$msg}", 75, "\n  ");

        if ($useRequired && is_callable($arg['required'])) {
            $msg .= "\n\n  ";
            $msg .= str_replace("\n", "\n  ", call_user_func($arg['required'], $args));
        } elseif (isset($arg['doc'])) {
            $msg .= wordwrap("\n\n  {$arg['doc']}", 75, "\n  ");
        }

        return $msg;
    }

    /**
     * Throw when an invalid type is encountered.
     *
     * @param string $name     Name of the value being validated.
     * @param mixed  $provided The provided value.
     * @throws \InvalidArgumentException
     */
    private function invalidType($name, $provided)
    {
        $expected = implode('|', $this->argDefinitions[$name]['valid']);
        $msg = "Invalid configuration value "
            . "provided for \"{$name}\". Expected {$expected}, but got "
            . describe_type($provided) . "\n\n"
            . $this->getArgMessage($name);
        throw new IAE($msg);
    }

    /**
     * Throws an exception for missing required arguments.
     *
     * @param array $args Passed in arguments.
     * @throws \InvalidArgumentException
     */
    private function throwRequired(array $args)
    {
        $missing = [];
        foreach ($this->argDefinitions as $k => $a) {
            if (empty($a['required'])
                || isset($a['default'])
                || isset($args[$k])
            ) {
                continue;
            }
            $missing[] = $this->getArgMessage($k, $args, true);
        }
        $msg = "Missing required client configuration options: \n\n";
        $msg .= implode("\n\n", $missing);
        throw new IAE($msg);
    }

    public static function _apply_retries($value, array &$args, HandlerList $list)
    {
        // A value of 0 for the config option disables retries
        if ($value) {
            $config = RetryConfigProvider::unwrap($value);

            if ($config->getMode() === 'legacy') {
                // # of retries is 1 less than # of attempts
                $decider = RetryMiddleware::createDefaultDecider(
                    $config->getMaxAttempts() - 1
                );
                $list->appendSign(
                    Middleware::retry($decider, null, $args['stats']['retries']),
                    'retry'
                );
            } else {
                $list->appendSign(
                    RetryMiddlewareV2::wrap(
                        $config,
                        ['collect_stats' => $args['stats']['retries']]
                    ),
                    'retry'
                );
            }
        }
    }

    public static function _apply_defaults($value, array &$args, HandlerList $list)
    {
        $config = ConfigModeProvider::unwrap($value);
        if ($config->getMode() !== 'legacy') {
            if (!isset($args['retries']) && !is_null($config->getRetryMode())) {
                $args['retries'] = ['mode' => $config->getRetryMode()];
            }
            if (
                !isset($args['sts_regional_endpoints'])
                && !is_null($config->getStsRegionalEndpoints())
            ) {
                $args['sts_regional_endpoints'] = ['mode' => $config->getStsRegionalEndpoints()];
            }
            if (
                !isset($args['s3_us_east_1_regional_endpoint'])
                && !is_null($config->getS3UsEast1RegionalEndpoints())
            ) {
                $args['s3_us_east_1_regional_endpoint'] = ['mode' => $config->getS3UsEast1RegionalEndpoints()];
            }

            if (!isset($args['http'])) {
                $args['http'] = [];
            }
            if (
                !isset($args['http']['connect_timeout'])
                && !is_null($config->getConnectTimeoutInMillis())
            ) {
                $args['http']['connect_timeout'] = $config->getConnectTimeoutInMillis() / 1000;
            }
            if (
                !isset($args['http']['timeout'])
                && !is_null($config->getHttpRequestTimeoutInMillis())
            ) {
                $args['http']['timeout'] = $config->getHttpRequestTimeoutInMillis() / 1000;
            }
        }
    }

    public static function _apply_disable_request_compression($value, array &$args) {
        if (is_callable($value)) {
            $value = $value();
        }
        if (!is_bool($value)) {
            throw new IAE(
                "Invalid configuration value provided for 'disable_request_compression'."
                . " value must be a bool."
            );
        }
        $args['config']['disable_request_compression'] = $value;
    }

    public static function _apply_min_compression_size($value, array &$args) {
        if (is_callable($value)) {
            $value = $value();
        }
        if (!is_int($value)
            || (is_int($value)
                && ($value < 0 || $value > 10485760))
        ) {
            throw new IAE(" Invalid configuration value provided for 'min_compression_size_bytes'."
                . " value must be an integer between 0 and 10485760, inclusive.");
        }
        $args['config']['request_min_compression_size_bytes'] = $value;
    }

    public static function _default_min_compression_size(array &$args) {
        return ConfigurationResolver::resolve(
            'request_min_compression_size_bytes',
            10240,
            'int',
            $args
        );
    }

    public static function _apply_credentials($value, array &$args)
    {
        if (is_callable($value)) {
            return;
        }

        if ($value instanceof CredentialsInterface) {
            $args['credentials'] = CredentialProvider::fromCredentials($value);
        } elseif (is_array($value)
            && isset($value['key'])
            && isset($value['secret'])
        ) {
            $args['credentials'] = CredentialProvider::fromCredentials(
                new Credentials(
                    $value['key'],
                    $value['secret'],
                    $value['token'] ?? null,
                    $value['expires'] ?? null,
                    $value['accountId'] ?? null
                )
            );
        } elseif ($value === false) {
            $args['credentials'] = CredentialProvider::fromCredentials(
                new Credentials('', '')
            );
            $args['config']['signature_version'] = 'anonymous';
            $args['config']['configured_signature_version'] = true;
        } elseif ($value instanceof CacheInterface) {
            $args['credentials'] = CredentialProvider::defaultProvider($args);
        } else {
            throw new IAE('Credentials must be an instance of '
                . "'" . CredentialsInterface::class . ', an associative '
                . 'array that contains "key", "secret", and an optional "token" '
                . 'key-value pairs, a credentials provider function, or false.');
        }
    }

    public static function _default_credential_provider(array $args)
    {
        return CredentialProvider::defaultProvider($args);
    }

    public static function _apply_token($value, array &$args)
    {
        if (is_callable($value)) {
            return;
        }

        if ($value instanceof Token) {
            $args['token'] = TokenProvider::fromToken($value);
        } elseif (is_array($value)
            && isset($value['token'])
        ) {
            $args['token'] = TokenProvider::fromToken(
                new Token(
                    $value['token'],
                    $value['expires'] ?? null
                )
            );
        } elseif ($value instanceof CacheInterface) {
            $args['token'] = TokenProvider::defaultProvider($args);
        } else {
            throw new IAE('Token must be an instance of '
                . TokenInterface::class . ', an associative '
                . 'array that contains "token" and an optional "expires" '
                . 'key-value pairs, a token provider function, or false.');
        }
    }

    public static function _default_token_provider(array $args)
    {
        return TokenProvider::defaultProvider($args);
    }

    public static function _apply_csm($value, array &$args, HandlerList $list)
    {
        if ($value === false) {
            $value = new Configuration(
                false,
                \Aws\ClientSideMonitoring\ConfigurationProvider::DEFAULT_HOST,
                \Aws\ClientSideMonitoring\ConfigurationProvider::DEFAULT_PORT,
                \Aws\ClientSideMonitoring\ConfigurationProvider::DEFAULT_CLIENT_ID
            );
            $args['csm'] = $value;
        }

        $list->appendBuild(
            ApiCallMonitoringMiddleware::wrap(
                $args['credentials'],
                $value,
                $args['region'],
                $args['api']->getServiceId()
            ),
            'ApiCallMonitoringMiddleware'
        );

        $list->appendAttempt(
            ApiCallAttemptMonitoringMiddleware::wrap(
                $args['credentials'],
                $value,
                $args['region'],
                $args['api']->getServiceId()
            ),
            'ApiCallAttemptMonitoringMiddleware'
        );
    }

    public static function _apply_api_provider(callable $value, array &$args)
    {
        $api = new Service(
            ApiProvider::resolve(
                $value,
                'api',
                $args['service'],
                $args['version']
            ),
            $value
        );

        if (
            empty($args['config']['signing_name'])
            && isset($api['metadata']['signingName'])
        ) {
            $args['config']['signing_name'] = $api['metadata']['signingName'];
        }

        $args['api'] = $api;
        $args['parser'] = Service::createParser($api);
        $args['error_parser'] = Service::createErrorParser($api->getProtocol(), $api);
    }

    public static function _apply_endpoint_provider($value, array &$args)
    {
        if (!isset($args['endpoint'])) {
            if ($value instanceof \Aws\EndpointV2\EndpointProviderV2) {
                $options = self::getEndpointProviderOptions($args);
                $value = PartitionEndpointProvider::defaultProvider($options)
                    ->getPartition($args['region'], $args['service']);
            }

            $endpointPrefix = $args['api']['metadata']['endpointPrefix'] ?? $args['service'];

            // Check region is a valid host label when it is being used to
            // generate an endpoint
            if (!self::isValidRegion($args['region'])) {
                throw new InvalidRegionException('Region must be a valid RFC'
                    . ' host label.');
            }
            $serviceEndpoints =
                is_array($value) && isset($value['services'][$args['service']]['endpoints'])
                    ? $value['services'][$args['service']]['endpoints']
                    : null;
            if (isset($serviceEndpoints[$args['region']]['deprecated'])) {
                trigger_error("The service " . $args['service'] . "has "
                    . " deprecated the region " . $args['region'] . ".",
                    E_USER_WARNING
                );
            }

            $args['region'] = \Aws\strip_fips_pseudo_regions($args['region']);

            // Invoke the endpoint provider and throw if it does not resolve.
            $result = EndpointProvider::resolve($value, [
                'service' => $endpointPrefix,
                'region'  => $args['region'],
                'scheme'  => $args['scheme'],
                'options' => self::getEndpointProviderOptions($args),
            ]);

            $args['endpoint'] = $result['endpoint'];

            if (empty($args['config']['signature_version'])) {
                if (
                    isset($args['api'])
                    && $args['api']->getSignatureVersion() == 'bearer'
                ) {
                    $args['config']['signature_version'] = 'bearer';
                } elseif (isset($result['signatureVersion'])) {
                    $args['config']['signature_version'] = $result['signatureVersion'];
                }
            }

            if (
                empty($args['config']['signing_region'])
                && isset($result['signingRegion'])
            ) {
                $args['config']['signing_region'] = $result['signingRegion'];
            }

            if (
                empty($args['config']['signing_name'])
                && isset($result['signingName'])
            ) {
                $args['config']['signing_name'] = $result['signingName'];
            }
        }
    }

    public static function _apply_endpoint_discovery($value, array &$args) {
        $args['endpoint_discovery'] = $value;
    }

    public static function _default_endpoint_discovery_provider(array $args)
    {
        return ConfigurationProvider::defaultProvider($args);
    }

    public static function _apply_use_fips_endpoint($value, array &$args) {
        if ($value instanceof CacheInterface) {
            $value = UseFipsConfigProvider::defaultProvider($args);
        }
        if (is_callable($value)) {
            $value = $value();
        }
        if ($value instanceof PromiseInterface) {
            $value = $value->wait();
        }
        if ($value instanceof UseFipsEndpointConfigurationInterface) {
            $args['config']['use_fips_endpoint'] = $value;
        } else {
            // The Configuration class itself will validate other inputs
            $args['config']['use_fips_endpoint'] = new UseFipsEndpointConfiguration($value);
        }
    }

    public static function _default_use_fips_endpoint(array &$args) {
        return UseFipsConfigProvider::defaultProvider($args);
    }

    public static function _apply_use_dual_stack_endpoint($value, array &$args) {
        if ($value instanceof CacheInterface) {
            $value = UseDualStackConfigProvider::defaultProvider($args);
        }
        if (is_callable($value)) {
            $value = $value();
        }
        if ($value instanceof PromiseInterface) {
            $value = $value->wait();
        }
        if ($value instanceof UseDualStackEndpointConfigurationInterface) {
            $args['config']['use_dual_stack_endpoint'] = $value;
        } else {
            // The Configuration class itself will validate other inputs
            $args['config']['use_dual_stack_endpoint'] =
                new UseDualStackEndpointConfiguration($value, $args['region']);
        }
    }

    public static function _default_use_dual_stack_endpoint(array &$args) {
        return UseDualStackConfigProvider::defaultProvider($args);
    }

    public static function _apply_serializer($value, array &$args, HandlerList $list)
    {
        $list->prependBuild(Middleware::requestBuilder($value), 'builder');
    }

    public static function _apply_debug($value, array &$args, HandlerList $list)
    {
        if ($value !== false) {
            $list->interpose(
                new TraceMiddleware(
                    $value === true ? [] : $value,
                    $args['api'])
            );
        }
    }

    public static function _apply_stats($value, array &$args, HandlerList $list)
    {
        // Create an array of stat collectors that are disabled (set to false)
        // by default. If the user has passed in true, enable all stat
        // collectors.
        $defaults = array_fill_keys(
            ['http', 'retries', 'timer'],
            $value === true
        );
        $args['stats'] = is_array($value)
            ? array_replace($defaults, $value)
            : $defaults;

        if ($args['stats']['timer']) {
            $list->prependInit(Middleware::timer(), 'timer');
        }
    }

    public static function _apply_profile($_, array &$args)
    {
        $args['credentials'] = CredentialProvider::ini($args['profile']);
    }

    public static function _apply_validate($value, array &$args, HandlerList $list)
    {
        if ($value === false) {
            return;
        }

        $validator = $value === true
            ? new Validator()
            : new Validator($value);
        $list->appendValidate(
            Middleware::validation($args['api'], $validator),
            'validation'
        );
    }

    public static function _apply_handler($value, array &$args, HandlerList $list)
    {
        $list->setHandler($value);
    }

    public static function _default_handler(array &$args)
    {
        return new WrappedHttpHandler(
            default_http_handler(),
            $args['parser'],
            $args['error_parser'],
            $args['exception_class'],
            $args['stats']['http']
        );
    }

    public static function _apply_http_handler($value, array &$args, HandlerList $list)
    {
        $args['handler'] = new WrappedHttpHandler(
            $value,
            $args['parser'],
            $args['error_parser'],
            $args['exception_class'],
            $args['stats']['http']
        );
    }

    public static function _apply_app_id($value, array &$args)
    {
        // AppId should not be longer than 50 chars
        static $MAX_APP_ID_LENGTH = 50;
        if (strlen($value) > $MAX_APP_ID_LENGTH) {
            trigger_error("The provided or configured value for `AppId`, "
                ."which is an user agent parameter, exceeds the maximum length of "
            ."$MAX_APP_ID_LENGTH characters.", E_USER_WARNING);
        }

        $args['app_id'] = $value;
    }

    public static function _default_app_id(array $args)
    {
        return ConfigurationResolver::resolve(
            'sdk_ua_app_id',
            '',
            'string',
            $args
        );
    }

    public static function _apply_user_agent(
        $inputUserAgent,
        array &$args,
        HandlerList $list
    ): void
    {
        // Add endpoint discovery if set
        $userAgent = [];
        // Add the input to the end
        if ($inputUserAgent){
            if (!is_array($inputUserAgent)) {
                $inputUserAgent = [$inputUserAgent];
            }
            $inputUserAgent = array_map('strval', $inputUserAgent);
            $userAgent = array_merge($userAgent, $inputUserAgent);
        }

        $args['ua_append'] = $userAgent;

        $list->appendBuild(
            Middleware::mapRequest(function (RequestInterface $request) use ($userAgent) {
                return $request->withHeader(
                    'X-Amz-User-Agent',
                    implode(' ', array_merge(
                        $userAgent,
                        $request->getHeader('X-Amz-User-Agent')
                    ))
                );
            })
        );
    }

    public static function _apply_endpoint($value, array &$args, HandlerList $list)
    {
        if (empty($value)) {
            unset($args['endpoint']);
            return;
        }

        $args['endpoint_override'] = true;
        $args['endpoint'] = $value;
    }

    public static function _apply_idempotency_auto_fill(
        $value,
        array &$args,
        HandlerList $list
    ) {
        $enabled = false;
        $generator = null;


        if (is_bool($value)) {
            $enabled = $value;
        } elseif (is_callable($value)) {
            $enabled = true;
            $generator = $value;
        }

        if ($enabled) {
            $list->prependInit(
                IdempotencyTokenMiddleware::wrap($args['api'], $generator),
                'idempotency_auto_fill'
            );
        }
    }

    public static function _default_account_id_endpoint_mode($args)
    {
        return ConfigurationResolver::resolve(
            'account_id_endpoint_mode',
            'preferred',
            'string',
            $args
        );
    }

    public static function _apply_account_id_endpoint_mode($value, array &$args)
    {
        static $accountIdEndpointModes = ['disabled', 'required', 'preferred'];
        if (!in_array($value, $accountIdEndpointModes)) {
            throw new IAE(
                "The value provided for the config account_id_endpoint_mode is invalid."
                ."Valid values are: " . implode(", ", $accountIdEndpointModes)
            );
        }

        $args['account_id_endpoint_mode'] = $value;
    }

    public static function _default_endpoint_provider(array $args)
    {
        $service = $args['api'] ?? null;
        $serviceName = isset($service) ? $service->getServiceName() : null;
        $apiVersion = isset($service) ? $service->getApiVersion() : null;

        if (self::isValidService($serviceName)
            && self::isValidApiVersion($serviceName, $apiVersion)
        ) {
            $ruleset = EndpointDefinitionProvider::getEndpointRuleset(
                $service->getServiceName(),
                $service->getApiVersion()
            );
            return new \Aws\EndpointV2\EndpointProviderV2(
                $ruleset,
                EndpointDefinitionProvider::getPartitions()
            );
        }
        $options = self::getEndpointProviderOptions($args);
        return PartitionEndpointProvider::defaultProvider($options)
            ->getPartition($args['region'], $args['service']);
    }

    public static function _default_serializer(array $args)
    {
        return Service::createSerializer(
            $args['api'],
            $args['endpoint']
        );
    }

    public static function _default_signature_provider()
    {
        return SignatureProvider::defaultProvider();
    }

    public static function _default_auth_scheme_resolver(array $args)
    {
        return new AuthSchemeResolver($args['credentials'], $args['token']);
    }

    public static function _default_signature_version(array &$args)
    {
        if (isset($args['config']['signature_version'])) {
            return $args['config']['signature_version'];
        }

        $args['__partition_result'] = isset($args['__partition_result'])
            ? isset($args['__partition_result'])
            : call_user_func(PartitionEndpointProvider::defaultProvider(), [
                'service' => $args['service'],
                'region' => $args['region'],
            ]);

        return isset($args['__partition_result']['signatureVersion'])
            ? $args['__partition_result']['signatureVersion']
            : $args['api']->getSignatureVersion();
    }

    public static function _default_signing_name(array &$args)
    {
        if (isset($args['config']['signing_name'])) {
            return $args['config']['signing_name'];
        }

        $args['__partition_result'] = isset($args['__partition_result'])
            ? isset($args['__partition_result'])
            : call_user_func(PartitionEndpointProvider::defaultProvider(), [
                'service' => $args['service'],
                'region' => $args['region'],
            ]);

        if (isset($args['__partition_result']['signingName'])) {
            return $args['__partition_result']['signingName'];
        }

        if ($signingName = $args['api']->getSigningName()) {
            return $signingName;
        }

        return $args['service'];
    }

    public static function _default_signing_region(array &$args)
    {
        if (isset($args['config']['signing_region'])) {
            return $args['config']['signing_region'];
        }

        $args['__partition_result'] = isset($args['__partition_result'])
            ? isset($args['__partition_result'])
            : call_user_func(PartitionEndpointProvider::defaultProvider(), [
                'service' => $args['service'],
                'region' => $args['region'],
            ]);

        return $args['__partition_result']['signingRegion'] ?? $args['region'];
    }

    public static function _apply_ignore_configured_endpoint_urls($value, array &$args)
    {
        $args['config']['ignore_configured_endpoint_urls'] = $value;
    }

    public static function _apply_suppress_php_deprecation_warning($value, &$args)
    {
        if ($value)  {
            $args['suppress_php_deprecation_warning'] = true;
        } elseif (!empty(getenv("AWS_SUPPRESS_PHP_DEPRECATION_WARNING"))) {
            $args['suppress_php_deprecation_warning']
                = \Aws\boolean_value(getenv("AWS_SUPPRESS_PHP_DEPRECATION_WARNING"));
        } elseif (!empty($_SERVER["AWS_SUPPRESS_PHP_DEPRECATION_WARNING"])) {
            $args['suppress_php_deprecation_warning'] =
                \Aws\boolean_value($_SERVER["AWS_SUPPRESS_PHP_DEPRECATION_WARNING"]);
        } elseif (!empty($_ENV["AWS_SUPPRESS_PHP_DEPRECATION_WARNING"])) {
            $args['suppress_php_deprecation_warning'] =
                \Aws\boolean_value($_ENV["AWS_SUPPRESS_PHP_DEPRECATION_WARNING"]);
        }

        if ($args['suppress_php_deprecation_warning'] === false
            && PHP_VERSION_ID < 80100
        ) {
            self::emitDeprecationWarning();
        }
    }

    public static function _default_endpoint(array &$args)
    {
        if ($args['config']['ignore_configured_endpoint_urls']
            || !self::isValidService($args['service'])
        ) {
            return '';
        }

        $serviceIdentifier = \Aws\manifest($args['service'])['serviceIdentifier'];
        $value =  ConfigurationResolver::resolve(
            'endpoint_url_' . $serviceIdentifier,
            '',
            'string',
            $args + [
                'ini_resolver_options' => [
                    'section' => 'services',
                    'subsection' => $serviceIdentifier,
                    'key' => 'endpoint_url'
                ]
            ]
        );

        if (empty($value)) {
            $value = ConfigurationResolver::resolve(
                'endpoint_url',
                '',
                'string',
                $args
            );
        }

        if (!empty($value)) {
            $args['config']['configured_endpoint_url'] = true;
        }

        return $value;
    }

    public static function _apply_sigv4a_signing_region_set($value, array &$args)
    {
        if (empty($value)) {
            $args['sigv4a_signing_region_set'] = null;
        } elseif (is_array($value)) {
            $args['sigv4a_signing_region_set'] = implode(', ', $value);
        } else {
            $args['sigv4a_signing_region_set'] = $value;
        }
    }

    public static function _apply_region($value, array &$args)
    {
        if (empty($value)) {
            self::_missing_region($args);
        }
        $args['region'] = $value;
    }

    public static function _missing_region(array $args)
    {
        $service = $args['service'] ?? '';

        $msg = <<<EOT
Missing required client configuration options:

region: (string)

A "region" configuration value is required for the "{$service}" service
(e.g., "us-west-2"). A list of available public regions and endpoints can be
found at http://docs.aws.amazon.com/general/latest/gr/rande.html.
EOT;
        throw new IAE($msg);
    }

    /**
     * Resolves a value from env or config.
     *
     * @param $key
     * @param $expectedType
     * @param $args
     *
     * @return mixed|string
     */
    private static function _resolve_from_env_ini(
        string $key,
        string $expectedType,
        array $args
    ) {
        static $typeDefaultMap = [
            'int' => 0,
            'bool' => false,
            'boolean' => false,
            'string' => '',
        ];

        return ConfigurationResolver::resolve(
            $key,
            $typeDefaultMap[$expectedType] ?? '',
            $expectedType,
            $args
        );
    }

    /**
     * Extracts client options for the endpoint provider to its own array
     *
     * @param array $args
     * @return array
     */
    private static function getEndpointProviderOptions(array $args)
    {
        $options = [];
        $optionKeys = [
            'sts_regional_endpoints',
            's3_us_east_1_regional_endpoint',
        ];
        $configKeys = [
            'use_dual_stack_endpoint',
            'use_fips_endpoint',
        ];
        foreach ($optionKeys as $key) {
            if (isset($args[$key])) {
                $options[$key] = $args[$key];
            }
        }
        foreach ($configKeys as $key) {
            if (isset($args['config'][$key])) {
                $options[$key] = $args['config'][$key];
            }
        }
        return $options;
    }

    /**
     * Validates a region to be used for endpoint construction
     *
     * @param $region
     * @return bool
     */
    private static function isValidRegion($region)
    {
        return is_valid_hostlabel($region);
    }

    private function _apply_client_context_params(array $args)
    {
        if (isset($args['api'])
            && !empty($args['api']->getClientContextParams()))
        {
            $clientContextParams = $args['api']->getClientContextParams();
            foreach($clientContextParams as $paramName => $paramDefinition) {
                $definition = [
                    'type' => 'value',
                    'valid' => [$paramDefinition['type']],
                    'doc' => $paramDefinition['documentation'] ?? null
                ];
                $this->argDefinitions[$paramName] = $definition;

                if (isset($args[$paramName])) {
                    $fn = self::$typeMap[$paramDefinition['type']];
                    if (!$fn($args[$paramName])) {
                        $this->invalidType($paramName, $args[$paramName]);
                    }
                }
            }
        }
    }

    private static function isValidService($service)
    {
        if (is_null($service)) {
            return false;
        }
        $services = \Aws\manifest();
        return isset($services[$service]);
    }

    private static function isValidApiVersion($service, $apiVersion)
    {
        if (is_null($apiVersion)) {
            return false;
        }
        return is_dir(
            __DIR__ . "/data/{$service}/$apiVersion"
        );
    }

    private static function emitDeprecationWarning()
    {
        $phpVersionString = phpversion();
        trigger_error(
            "This installation of the SDK is using PHP version"
            .  " {$phpVersionString}, which will be deprecated on January"
            .  " 13th, 2025.\nPlease upgrade your PHP version to a minimum of"
            .  " 8.1.x to continue receiving updates for the AWS"
            .  " SDK for PHP.\nTo disable this warning, set"
            .  " suppress_php_deprecation_warning to true on the client constructor"
            .  " or set the environment variable AWS_SUPPRESS_PHP_DEPRECATION_WARNING"
            .  " to true.\nMore information can be found at: "
            .   "https://aws.amazon.com/blogs/developer/announcing-the-end-of-support-for-php-runtimes-8-0-x-and-below-in-the-aws-sdk-for-php/\n",
            E_USER_DEPRECATED
        );
    }
}
