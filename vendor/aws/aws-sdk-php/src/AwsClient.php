<?php
namespace Aws;

use Aws\Api\ApiProvider;
use Aws\Api\DocModel;
use Aws\Api\Service;
use Aws\Auth\AuthSelectionMiddleware;
use Aws\Auth\AuthSchemeResolverInterface;
use Aws\EndpointDiscovery\EndpointDiscoveryMiddleware;
use Aws\EndpointV2\EndpointProviderV2;
use Aws\EndpointV2\EndpointV2Middleware;
use Aws\Exception\AwsException;
use Aws\Signature\SignatureProvider;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;

/**
 * Default AWS client implementation
 */
class AwsClient implements AwsClientInterface
{
    use AwsClientTrait;

    /** @var array */
    private $aliases;

    /** @var array */
    private $config;

    /** @var string */
    private $region;

    /** @var string */
    private $signingRegionSet;

    /** @var string */
    private $endpoint;

    /** @var Service */
    private $api;

    /** @var callable */
    private $signatureProvider;

    /** @var AuthSchemeResolverInterface */
    private $authSchemeResolver;

    /** @var callable */
    private $credentialProvider;

    /** @var callable */
    private $tokenProvider;

    /** @var HandlerList */
    private $handlerList;

    /** @var array*/
    private $defaultRequestOptions;

    /** @var array*/
    private $clientContextParams = [];

    /** @var array*/
    protected $clientBuiltIns = [];

    /** @var  EndpointProviderV2 | callable */
    protected $endpointProvider;

    /** @var callable */
    protected $serializer;

    /**
     * Get an array of client constructor arguments used by the client.
     *
     * @return array
     */
    public static function getArguments()
    {
        return ClientResolver::getDefaultArguments();
    }

    /**
     * The client constructor accepts the following options:
     *
     * - api_provider: (callable) An optional PHP callable that accepts a
     *   type, service, and version argument, and returns an array of
     *   corresponding configuration data. The type value can be one of api,
     *   waiter, or paginator.
     * - credentials:
     *   (Aws\Credentials\CredentialsInterface|array|bool|callable) Specifies
     *   the credentials used to sign requests. Provide an
     *   Aws\Credentials\CredentialsInterface object, an associative array of
     *   "key", "secret", and an optional "token" key, `false` to use null
     *   credentials, or a callable credentials provider used to create
     *   credentials or return null. See Aws\Credentials\CredentialProvider for
     *   a list of built-in credentials providers. If no credentials are
     *   provided, the SDK will attempt to load them from the environment.
     * - token:
     *   (Aws\Token\TokenInterface|array|bool|callable) Specifies
     *   the token used to authorize requests. Provide an
     *   Aws\Token\TokenInterface object, an associative array of
     *   "token" and an optional "expires" key, `false` to use no
     *   token, or a callable token provider used to create a
     *   token or return null. See Aws\Token\TokenProvider for
     *   a list of built-in token providers. If no token is
     *   provided, the SDK will attempt to load one from the environment.
     * - csm:
     *   (Aws\ClientSideMonitoring\ConfigurationInterface|array|callable) Specifies
     *   the credentials used to sign requests. Provide an
     *   Aws\ClientSideMonitoring\ConfigurationInterface object, a callable
     *   configuration provider used to create client-side monitoring configuration,
     *   `false` to disable csm, or an associative array with the following keys:
     *   enabled: (bool) Set to true to enable client-side monitoring, defaults
     *   to false; host: (string) the host location to send monitoring events to,
     *   defaults to 127.0.0.1; port: (int) The port used for the host connection,
     *   defaults to 31000; client_id: (string) An identifier for this project
     * - debug: (bool|array) Set to true to display debug information when
     *   sending requests. Alternatively, you can provide an associative array
     *   with the following keys: logfn: (callable) Function that is invoked
     *   with log messages; stream_size: (int) When the size of a stream is
     *   greater than this number, the stream data will not be logged (set to
     *   "0" to not log any stream data); scrub_auth: (bool) Set to false to
     *   disable the scrubbing of auth data from the logged messages; http:
     *   (bool) Set to false to disable the "debug" feature of lower level HTTP
     *   adapters (e.g., verbose curl output).
     * - stats: (bool|array) Set to true to gather transfer statistics on
     *   requests sent. Alternatively, you can provide an associative array with
     *   the following keys: retries: (bool) Set to false to disable reporting
     *   on retries attempted; http: (bool) Set to true to enable collecting
     *   statistics from lower level HTTP adapters (e.g., values returned in
     *   GuzzleHttp\TransferStats). HTTP handlers must support an
     *   `http_stats_receiver` option for this to have an effect; timer: (bool)
     *   Set to true to enable a command timer that reports the total wall clock
     *   time spent on an operation in seconds.
     * - disable_host_prefix_injection: (bool) Set to true to disable host prefix
     *   injection logic for services that use it. This disables the entire
     *   prefix injection, including the portions supplied by user-defined
     *   parameters. Setting this flag will have no effect on services that do
     *   not use host prefix injection.
     * - endpoint: (string) The full URI of the webservice. This is only
     *   required when connecting to a custom endpoint (e.g., a local version
     *   of S3).
     * - endpoint_discovery: (Aws\EndpointDiscovery\ConfigurationInterface,
     *   Aws\CacheInterface, array, callable) Settings for endpoint discovery.
     *   Provide an instance of Aws\EndpointDiscovery\ConfigurationInterface,
     *   an instance Aws\CacheInterface, a callable that provides a promise for
     *   a Configuration object, or an associative array with the following
     *   keys: enabled: (bool) Set to true to enable endpoint discovery, false
     *   to explicitly disable it, defaults to false; cache_limit: (int) The
     *   maximum number of keys in the endpoints cache, defaults to 1000.
     * - endpoint_provider: (callable) An optional PHP callable that
     *   accepts a hash of options including a "service" and "region" key and
     *   returns NULL or a hash of endpoint data, of which the "endpoint" key
     *   is required. See Aws\Endpoint\EndpointProvider for a list of built-in
     *   providers.
     * - handler: (callable) A handler that accepts a command object,
     *   request object and returns a promise that is fulfilled with an
     *   Aws\ResultInterface object or rejected with an
     *   Aws\Exception\AwsException. A handler does not accept a next handler
     *   as it is terminal and expected to fulfill a command. If no handler is
     *   provided, a default Guzzle handler will be utilized.
     * - http: (array, default=array(0)) Set to an array of SDK request
     *   options to apply to each request (e.g., proxy, verify, etc.).
     * - http_handler: (callable) An HTTP handler is a function that
     *   accepts a PSR-7 request object and returns a promise that is fulfilled
     *   with a PSR-7 response object or rejected with an array of exception
     *   data. NOTE: This option supersedes any provided "handler" option.
     * - idempotency_auto_fill: (bool|callable) Set to false to disable SDK to
     *   populate parameters that enabled 'idempotencyToken' trait with a random
     *   UUID v4 value on your behalf. Using default value 'true' still allows
     *   parameter value to be overwritten when provided. Note: auto-fill only
     *   works when cryptographically secure random bytes generator functions
     *   (random_bytes, openssl_random_pseudo_bytes or mcrypt_create_iv) can be
     *   found. You may also provide a callable source of random bytes.
     * - profile: (string) Allows you to specify which profile to use when
     *   credentials are created from the AWS credentials file in your HOME
     *   directory. This setting overrides the AWS_PROFILE environment
     *   variable. Note: Specifying "profile" will cause the "credentials" key
     *   to be ignored.
     * - region: (string, required) Region to connect to. See
     *   http://docs.aws.amazon.com/general/latest/gr/rande.html for a list of
     *   available regions.
     * - retries: (int, Aws\Retry\ConfigurationInterface, Aws\CacheInterface,
     *   array, callable) Configures the retry mode and maximum number of
     *   allowed retries for a client (pass 0 to disable retries). Provide an
     *   integer for 'legacy' mode with the specified number of retries.
     *   Otherwise provide an instance of Aws\Retry\ConfigurationInterface, an
     *   instance of  Aws\CacheInterface, a callable function, or an array with
     *   the following keys: mode: (string) Set to 'legacy', 'standard' (uses
     *   retry quota management), or 'adapative' (an experimental mode that adds
     *   client-side rate limiting to standard mode); max_attempts (int) The
     *   maximum number of attempts for a given request.
     * - scheme: (string, default=string(5) "https") URI scheme to use when
     *   connecting connect. The SDK will utilize "https" endpoints (i.e.,
     *   utilize SSL/TLS connections) by default. You can attempt to connect to
     *   a service over an unencrypted "http" endpoint by setting ``scheme`` to
     *   "http".
     * - signature_provider: (callable) A callable that accepts a signature
     *   version name (e.g., "v4"), a service name, and region, and
     *   returns a SignatureInterface object or null. This provider is used to
     *   create signers utilized by the client. See
     *   Aws\Signature\SignatureProvider for a list of built-in providers
     * - signature_version: (string) A string representing a custom
     *   signature version to use with a service (e.g., v4). Note that
     *   per/operation signature version MAY override this requested signature
     *   version.
     * - use_aws_shared_config_files: (bool, default=bool(true)) Set to false to
     *   disable checking for shared config file in '~/.aws/config' and
     *   '~/.aws/credentials'.  This will override the AWS_CONFIG_FILE
     *   environment variable.
     * - validate: (bool, default=bool(true)) Set to false to disable
     *   client-side parameter validation.
     * - version: (string, required) The version of the webservice to
     *   utilize (e.g., 2006-03-01).
     * - account_id_endpoint_mode: (string, default(preferred)) this option
     *   decides whether credentials should resolve an accountId value,
     *   which is going to be used as part of the endpoint resolution.
     *   The valid values for this option are:
     *   - preferred: when this value is set then, a warning is logged when
     *     accountId is empty in the resolved identity.
     *   - required: when this value is set then, an exception is thrown when
     *     accountId is empty in the resolved identity.
     *   - disabled: when this value is set then, the validation for if accountId
     *     was resolved or not, is ignored.
     * - ua_append: (string, array) To pass custom user agent parameters.
     * - app_id: (string) an optional application specific identifier that can be set.
     *   When set it will be appended to the User-Agent header of every request
     *   in the form of App/{AppId}. This variable is sourced from environment
     *   variable AWS_SDK_UA_APP_ID or the shared config profile attribute sdk_ua_app_id.
     *   See https://docs.aws.amazon.com/sdkref/latest/guide/settings-reference.html for
     *   more information on environment variables and shared config settings.
     *
     * @param array $args Client configuration arguments.
     *
     * @throws \InvalidArgumentException if any required options are missing or
     *                                   the service is not supported.
     */
    public function __construct(array $args)
    {
        list($service, $exceptionClass) = $this->parseClass();
        if (!isset($args['service'])) {
            $args['service'] = manifest($service)['endpoint'];
        }
        if (!isset($args['exception_class'])) {
            $args['exception_class'] = $exceptionClass;
        }
        $this->handlerList = new HandlerList();
        $resolver = new ClientResolver(static::getArguments());
        $config = $resolver->resolve($args, $this->handlerList);
        $this->api = $config['api'];
        $this->signatureProvider = $config['signature_provider'];
        $this->authSchemeResolver = $config['auth_scheme_resolver'];
        $this->endpoint = new Uri($config['endpoint']);
        $this->credentialProvider = $config['credentials'];
        $this->tokenProvider = $config['token'];
        $this->region = $config['region'] ?? null;
        $this->signingRegionSet = $config['sigv4a_signing_region_set'] ?? null;
        $this->config = $config['config'];
        $this->setClientBuiltIns($args, $config);
        $this->clientContextParams = $this->setClientContextParams($args);
        $this->defaultRequestOptions = $config['http'];
        $this->endpointProvider = $config['endpoint_provider'];
        $this->serializer = $config['serializer'];
        $this->addSignatureMiddleware($args);
        $this->addInvocationId();
        $this->addEndpointParameterMiddleware($args);
        $this->addEndpointDiscoveryMiddleware($config, $args);
        $this->addRequestCompressionMiddleware($config);
        $this->loadAliases();
        $this->addStreamRequestPayload();
        $this->addRecursionDetection();
        if ($this->isUseEndpointV2()) {
            $this->addEndpointV2Middleware();
        }
        $this->addAuthSelectionMiddleware();

        if (!is_null($this->api->getMetadata('awsQueryCompatible'))) {
            $this->addQueryCompatibleInputMiddleware($this->api);
            $this->addQueryModeHeader();
        }

        if (isset($args['with_resolved'])) {
            $args['with_resolved']($config);
        }
        $this->addUserAgentMiddleware($config);
    }

    public function getHandlerList()
    {
        return $this->handlerList;
    }

    public function getConfig($option = null)
    {
        return $option === null
            ? $this->config
            : $this->config[$option] ?? null;
    }

    public function getCredentials()
    {
        $fn = $this->credentialProvider;
        return $fn();
    }


    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function getRegion()
    {
        return $this->region;
    }

    public function getApi()
    {
        return $this->api;
    }

    public function getCommand($name, array $args = [])
    {
        // Fail fast if the command cannot be found in the description.
        if (!isset($this->getApi()['operations'][$name])) {
            $name = ucfirst($name);
            if (!isset($this->getApi()['operations'][$name])) {
                throw new \InvalidArgumentException("Operation not found: $name");
            }
        }

        if (!isset($args['@http'])) {
            $args['@http'] = $this->defaultRequestOptions;
        } else {
            $args['@http'] += $this->defaultRequestOptions;
        }

        return new Command($name, $args, clone $this->getHandlerList());
    }

    public function getEndpointProvider()
    {
        return $this->endpointProvider;
    }

    /**
     * Provides the set of service context parameter
     * key-value pairs used for endpoint resolution.
     *
     * @return array
     */
    public function getClientContextParams()
    {
        return $this->clientContextParams;
    }

    /**
     * Provides the set of built-in keys and values
     * used for endpoint resolution
     *
     * @return array
     */
    public function getClientBuiltIns()
    {
        return $this->clientBuiltIns;
    }

    public function __sleep()
    {
        throw new \RuntimeException('Instances of ' . static::class
            . ' cannot be serialized');
    }

    /**
     * Get the signature_provider function of the client.
     *
     * @return callable
     */
    final public function getSignatureProvider()
    {
        return $this->signatureProvider;
    }

    /**
     * Parse the class name and setup the custom exception class of the client
     * and return the "service" name of the client and "exception_class".
     *
     * @return array
     */
    private function parseClass()
    {
        $klass = get_class($this);

        if ($klass === __CLASS__) {
            return ['', AwsException::class];
        }

        $service = substr($klass, strrpos($klass, '\\') + 1, -6);

        return [
            strtolower($service),
            "Aws\\{$service}\\Exception\\{$service}Exception"
        ];
    }

    private function addEndpointParameterMiddleware($args)
    {
        if (empty($args['disable_host_prefix_injection'])) {
            $list = $this->getHandlerList();
            $list->appendBuild(
                EndpointParameterMiddleware::wrap(
                    $this->api
                ),
                'endpoint_parameter'
            );
        }
    }

    private function addEndpointDiscoveryMiddleware($config, $args)
    {
        $list = $this->getHandlerList();

        if (!isset($args['endpoint'])) {
            $list->appendBuild(
                EndpointDiscoveryMiddleware::wrap(
                    $this,
                    $args,
                    $config['endpoint_discovery']
                ),
                'EndpointDiscoveryMiddleware'
            );
        }
    }

    private function addSignatureMiddleware(array $args)
    {
        $api = $this->getApi();
        $provider = $this->signatureProvider;
        $signatureVersion = $this->config['signature_version'];
        $name = $this->config['signing_name'];
        $region = $this->config['signing_region'];
        $signingRegionSet = $this->signingRegionSet;

        if (isset($args['signature_version'])
         || isset($this->config['configured_signature_version'])
        ) {
            $configuredSignatureVersion = true;
        } else {
            $configuredSignatureVersion = false;
        }

        $resolver = static function (
            CommandInterface $command
        ) use (
                $api,
                $provider,
                $name,
                $region,
                $signatureVersion,
                $configuredSignatureVersion,
                $signingRegionSet
        ) {
            if (!$configuredSignatureVersion) {
                if (!empty($command['@context']['signing_region'])) {
                    $region = $command['@context']['signing_region'];
                }
                if (!empty($command['@context']['signing_service'])) {
                    $name = $command['@context']['signing_service'];
                }
                if (!empty($command['@context']['signature_version'])) {
                    $signatureVersion = $command['@context']['signature_version'];
                }

                $authType = $api->getOperation($command->getName())['authtype'];
                switch ($authType){
                    case 'none':
                        $signatureVersion = 'anonymous';
                        break;
                    case 'v4-unsigned-body':
                        $signatureVersion = 'v4-unsigned-body';
                        break;
                    case 'bearer':
                        $signatureVersion = 'bearer';
                        break;
                }
            }

            if ($signatureVersion === 'v4a') {
                $commandSigningRegionSet = !empty($command['@context']['signing_region_set'])
                    ? implode(', ', $command['@context']['signing_region_set'])
                    : null;

                $region = $signingRegionSet
                    ?? $commandSigningRegionSet
                    ?? $region;
            }

            // Capture signature metric
            $command->getMetricsBuilder()->identifyMetricByValueAndAppend(
                'signature',
                $signatureVersion
            );

            return SignatureProvider::resolve($provider, $signatureVersion, $name, $region);
        };
        $this->handlerList->appendSign(
            Middleware::signer($this->credentialProvider,
                $resolver,
                $this->tokenProvider,
                $this->getConfig()
            ),
            'signer'
        );
    }

    private function addRequestCompressionMiddleware($config)
    {
        if (empty($config['disable_request_compression'])) {
            $list = $this->getHandlerList();
            $list->appendBuild(
                RequestCompressionMiddleware::wrap($config),
                'request-compression'
            );
        }
    }

    private function addQueryCompatibleInputMiddleware(Service $api)
    {
            $list = $this->getHandlerList();
            $list->appendValidate(
                QueryCompatibleInputMiddleware::wrap($api),
                'query-compatible-input'
            );
    }

    private function addQueryModeHeader(): void
    {
        $list = $this->getHandlerList();
        $list->appendBuild(
            Middleware::mapRequest(function (RequestInterface $r) {
                return $r->withHeader(
                    'x-amzn-query-mode',
                    "true"
                );
            }),
            'x-amzn-query-mode-header'
        );
    }

    private function addInvocationId()
    {
        // Add invocation id to each request
        $this->handlerList->prependSign(Middleware::invocationId(), 'invocation-id');
    }

    private function loadAliases($file = null)
    {
        if (!isset($this->aliases)) {
            if (is_null($file)) {
                $file = __DIR__ . '/data/aliases.json';
            }
            $aliases = \Aws\load_compiled_json($file);
            $serviceId = $this->api->getServiceId();
            $version = $this->getApi()->getApiVersion();
            if (!empty($aliases['operations'][$serviceId][$version])) {
                $this->aliases = array_flip($aliases['operations'][$serviceId][$version]);
            }
        }
    }

    private function addStreamRequestPayload()
    {
        $streamRequestPayloadMiddleware = StreamRequestPayloadMiddleware::wrap(
            $this->api
        );

        $this->handlerList->prependSign(
            $streamRequestPayloadMiddleware,
            'StreamRequestPayloadMiddleware'
        );
    }

    private function addRecursionDetection()
    {
        // Add recursion detection header to requests
        // originating in supported Lambda runtimes
        $this->handlerList->appendBuild(
            Middleware::recursionDetection(), 'recursion-detection'
        );
    }

    private function addAuthSelectionMiddleware()
    {
        $list = $this->getHandlerList();

        $list->prependBuild(
            AuthSelectionMiddleware::wrap(
                $this->authSchemeResolver,
                $this->getApi()
            ),
            'auth-selection'
        );
    }

    private function addEndpointV2Middleware()
    {
        $list = $this->getHandlerList();
        $endpointArgs = $this->getEndpointProviderArgs();

        $list->prependBuild(
            EndpointV2Middleware::wrap(
                $this->endpointProvider,
                $this->getApi(),
                $endpointArgs,
                $this->credentialProvider
            ),
            'endpoint-resolution'
        );
    }

    /**
     * Appends the user agent middleware.
     * This middleware MUST be appended after the
     * signature middleware `addSignatureMiddleware`,
     * so that metrics around signatures are properly
     * captured.
     *
     * @param $args
     * @return void
     */
    private function addUserAgentMiddleware($args)
    {
        $this->getHandlerList()->appendSign(
            UserAgentMiddleware::wrap($args),
            'user-agent'
        );
    }

    /**
     * Retrieves client context param definition from service model,
     * creates mapping of client context param names with client-provided
     * values.
     *
     * @return array
     */
    private function setClientContextParams($args)
    {
        $api = $this->getApi();
        $resolvedParams = [];
        if (!empty($paramDefinitions = $api->getClientContextParams())) {
            foreach($paramDefinitions as $paramName => $paramValue) {
                if (isset($args[$paramName])) {
                   $resolvedParams[$paramName] = $args[$paramName];
               }
            }
        }
        return $resolvedParams;
    }

    /**
     * Retrieves and sets default values used for endpoint resolution.
     */
    private function setClientBuiltIns($args, $resolvedConfig)
    {
        $builtIns = [];
        $config = $resolvedConfig['config'];
        $service = $args['service'];

        $builtIns['SDK::Endpoint'] = null;
        if (!empty($args['endpoint'])) {
            $builtIns['SDK::Endpoint'] = $args['endpoint'];
        } elseif (isset($config['configured_endpoint_url'])) {
            $builtIns['SDK::Endpoint'] = (string) $this->getEndpoint();
        }
        $builtIns['AWS::Region'] = $this->getRegion();
        $builtIns['AWS::UseFIPS'] = $config['use_fips_endpoint']->isUseFipsEndpoint();
        $builtIns['AWS::UseDualStack'] = $config['use_dual_stack_endpoint']->isUseDualstackEndpoint();
        if ($service === 's3' || $service === 's3control'){
            $builtIns['AWS::S3::UseArnRegion'] = $config['use_arn_region']->isUseArnRegion();
        }
        if ($service === 's3') {
            $builtIns['AWS::S3::UseArnRegion'] = $config['use_arn_region']->isUseArnRegion();
            $builtIns['AWS::S3::Accelerate'] = $config['use_accelerate_endpoint'];
            $builtIns['AWS::S3::ForcePathStyle'] = $config['use_path_style_endpoint'];
            $builtIns['AWS::S3::DisableMultiRegionAccessPoints'] = $config['disable_multiregion_access_points'];
        }
        $builtIns['AWS::Auth::AccountIdEndpointMode'] = $resolvedConfig['account_id_endpoint_mode'];

        $this->clientBuiltIns += $builtIns;
    }

    /**
     * Retrieves arguments to be used in endpoint resolution.
     *
     * @return array
     */
    public function getEndpointProviderArgs()
    {
        return $this->normalizeEndpointProviderArgs();
    }

    /**
     * Combines built-in and client context parameter values in
     * order of specificity.  Client context parameter values supersede
     * built-in values.
     *
     * @return array
     */
    private function normalizeEndpointProviderArgs()
    {
        $normalizedBuiltIns = [];

        foreach($this->clientBuiltIns as $name => $value) {
            $normalizedName = explode('::', $name);
            $normalizedName = $normalizedName[count($normalizedName) - 1];
            $normalizedBuiltIns[$normalizedName] = $value;
        }

        return array_merge($normalizedBuiltIns, $this->getClientContextParams());
    }

    protected function isUseEndpointV2()
    {
        return $this->endpointProvider instanceof EndpointProviderV2;
    }

    public static function emitDeprecationWarning() {
        trigger_error(
            "This method is deprecated. It will be removed in an upcoming release."
            , E_USER_DEPRECATED
        );

        $phpVersion = PHP_VERSION_ID;
        if ($phpVersion <  70205) {
            $phpVersionString = phpversion();
            @trigger_error(
                "This installation of the SDK is using PHP version"
                .  " {$phpVersionString}, which will be deprecated on August"
                .  " 15th, 2023.  Please upgrade your PHP version to a minimum of"
                .  " 7.2.5 before then to continue receiving updates to the AWS"
                .  " SDK for PHP.  To disable this warning, set"
                .  " suppress_php_deprecation_warning to true on the client constructor"
                .  " or set the environment variable AWS_SUPPRESS_PHP_DEPRECATION_WARNING"
                .  " to true.",
                E_USER_DEPRECATED
            );
        }
    }


    /**
     * Returns a service model and doc model with any necessary changes
     * applied.
     *
     * @param array $api  Array of service data being documented.
     * @param array $docs Array of doc model data.
     *
     * @return array Tuple containing a [Service, DocModel]
     *
     * @internal This should only used to document the service API.
     * @codeCoverageIgnore
     */
    public static function applyDocFilters(array $api, array $docs)
    {
        $aliases = \Aws\load_compiled_json(__DIR__ . '/data/aliases.json');
        $serviceId = $api['metadata']['serviceId'] ?? '';
        $version = $api['metadata']['apiVersion'];

        // Replace names for any operations with SDK aliases
        if (!empty($aliases['operations'][$serviceId][$version])) {
            foreach ($aliases['operations'][$serviceId][$version] as $op => $alias) {
                $api['operations'][$alias] = $api['operations'][$op];
                $docs['operations'][$alias] = $docs['operations'][$op];
                unset($api['operations'][$op], $docs['operations'][$op]);
            }
        }
        ksort($api['operations']);

        return [
            new Service($api, ApiProvider::defaultProvider()),
            new DocModel($docs)
        ];
    }

    /**
     * @deprecated
     * @return static
     */
    public static function factory(array $config = [])
    {
        return new static($config);
    }
}
