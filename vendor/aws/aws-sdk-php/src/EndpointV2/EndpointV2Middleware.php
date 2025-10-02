<?php
namespace Aws\EndpointV2;

use Aws\Api\Operation;
use Aws\Api\Service;
use Aws\Auth\Exception\UnresolvedAuthSchemeException;
use Aws\CommandInterface;
use Aws\MetricsBuilder;
use Closure;
use GuzzleHttp\Promise\Promise;
use Aws\EndpointV2\Ruleset\RulesetEndpoint;
use function JmesPath\search;

/**
 * Handles endpoint rule evaluation and endpoint resolution.
 *
 * IMPORTANT: this middleware must be added to the "build" step.
 * Specifically, it must precede the 'builder' step.
 *
 * @internal
 */
class EndpointV2Middleware
{
    const ACCOUNT_ID_PARAM = 'AccountId';
    const ACCOUNT_ID_ENDPOINT_MODE_PARAM = 'AccountIdEndpointMode';
    private static $validAuthSchemes = [
        'sigv4' => 'v4',
        'sigv4a' => 'v4a',
        'none' => 'anonymous',
        'bearer' => 'bearer',
        'sigv4-s3express' => 'v4-s3express'
    ];

    /** @var callable */
    private $nextHandler;

    /** @var EndpointProviderV2 */
    private $endpointProvider;

    /** @var Service */
    private $api;

    /** @var array */
    private $clientArgs;

    /** @var Closure */
    private $credentialProvider;

    /**
     * Create a middleware wrapper function
     *
     * @param EndpointProviderV2 $endpointProvider
     * @param Service $api
     * @param array $args
     * @param callable $credentialProvider
     *
     * @return Closure
     */
    public static function wrap(
        EndpointProviderV2 $endpointProvider,
        Service $api,
        array $args,
        callable $credentialProvider
    ) : Closure
    {
        return function (callable $handler) use ($endpointProvider, $api, $args, $credentialProvider) {
            return new self($handler, $endpointProvider, $api, $args, $credentialProvider);
        };
    }

    /**
     * @param callable $nextHandler
     * @param EndpointProviderV2 $endpointProvider
     * @param Service $api
     * @param array $args
     */
    public function __construct(
        callable $nextHandler,
        EndpointProviderV2 $endpointProvider,
        Service $api,
        array $args,
        ?callable $credentialProvider = null
    )
    {
        $this->nextHandler = $nextHandler;
        $this->endpointProvider = $endpointProvider;
        $this->api = $api;
        $this->clientArgs = $args;
        $this->credentialProvider = $credentialProvider;
    }

    /**
     * @param CommandInterface $command
     *
     * @return Promise
     */
    public function __invoke(CommandInterface $command)
    {
        $nextHandler = $this->nextHandler;
        $operation = $this->api->getOperation($command->getName());
        $commandArgs = $command->toArray();
        $providerArgs = $this->resolveArgs($commandArgs, $operation);

        $endpoint = $this->endpointProvider->resolveEndpoint($providerArgs);

        $this->appendEndpointMetrics($providerArgs, $endpoint, $command);

        if (!empty($authSchemes = $endpoint->getProperty('authSchemes'))) {
            $this->applyAuthScheme(
                $authSchemes,
                $command
            );
        }

        return $nextHandler($command, $endpoint);
    }

    /**
     * Resolves client, context params, static context params and endpoint provider
     * arguments provided at the command level.
     *
     * @param array $commandArgs
     * @param Operation $operation
     *
     * @return array
     */
    private function resolveArgs(array $commandArgs, Operation $operation): array
    {
        $rulesetParams = $this->endpointProvider->getRuleset()->getParameters();

        if (isset($rulesetParams[self::ACCOUNT_ID_PARAM])
            && isset($rulesetParams[self::ACCOUNT_ID_ENDPOINT_MODE_PARAM])) {
            $this->clientArgs[self::ACCOUNT_ID_PARAM] = $this->resolveAccountId();
        }

        $endpointCommandArgs = $this->filterEndpointCommandArgs(
            $rulesetParams,
            $commandArgs
        );
        $staticContextParams = $this->bindStaticContextParams(
            $operation->getStaticContextParams()
        );
        $contextParams = $this->bindContextParams(
            $commandArgs, $operation->getContextParams()
        );
        $operationContextParams = $this->bindOperationContextParams(
            $commandArgs,
            $operation->getOperationContextParams()
        );

        return array_merge(
            $this->clientArgs,
            $operationContextParams,
            $contextParams,
            $staticContextParams,
            $endpointCommandArgs
        );
    }

    /**
     * Compares Ruleset parameters against Command arguments
     * to create a mapping of arguments to pass into the
     * endpoint provider for endpoint resolution.
     *
     * @param array $rulesetParams
     * @param array $commandArgs
     * @return array
     */
    private function filterEndpointCommandArgs(
        array $rulesetParams,
        array $commandArgs
    ): array
    {
        $endpointMiddlewareOpts = [
            '@use_dual_stack_endpoint' => 'UseDualStack',
            '@use_accelerate_endpoint' => 'Accelerate',
            '@use_path_style_endpoint' => 'ForcePathStyle'
        ];

        $filteredArgs = [];

        foreach($rulesetParams as $name => $value) {
            if (isset($commandArgs[$name])) {
                if (!empty($value->getBuiltIn())) {
                    continue;
                }
                $filteredArgs[$name] = $commandArgs[$name];
            }
        }

        if ($this->api->getServiceName() === 's3') {
            foreach($endpointMiddlewareOpts as $optionName => $newValue) {
                if (isset($commandArgs[$optionName])) {
                    $filteredArgs[$newValue] = $commandArgs[$optionName];
                }
            }
        }

        return $filteredArgs;
    }

    /**
     * Binds static context params to their corresponding values.
     *
     * @param $staticContextParams
     *
     * @return array
     */
    private function bindStaticContextParams($staticContextParams): array
    {
        $scopedParams = [];

        forEach($staticContextParams as $paramName => $paramValue) {
            $scopedParams[$paramName] = $paramValue['value'];
        }

        return $scopedParams;
    }

    /**
     * Binds context params to their corresponding values found in
     * command arguments.
     *
     * @param array $commandArgs
     * @param array $contextParams
     *
     * @return array
     */
    private function bindContextParams(
        array $commandArgs,
        array $contextParams
    ): array
    {
        $scopedParams = [];

        foreach($contextParams as $name => $spec) {
            if (isset($commandArgs[$spec['shape']])) {
                $scopedParams[$name] = $commandArgs[$spec['shape']];
            }
        }

        return $scopedParams;
    }

    /**
     * Binds context params to their corresponding values found in
     * command arguments.
     *
     * @param array $commandArgs
     * @param array $contextParams
     *
     * @return array
     */
    private function bindOperationContextParams(
        array $commandArgs,
        array $operationContextParams
    ): array
    {
        $scopedParams = [];

        foreach($operationContextParams as $name => $spec) {
            $scopedValue = search($spec['path'], $commandArgs);

            if ($scopedValue) {
                $scopedParams[$name] = $scopedValue;
            }
        }

        return $scopedParams;
    }

    /**
     * Applies resolved auth schemes to the command object.
     *
     * @param $authSchemes
     * @param $command
     *
     * @return void
     */
    private function applyAuthScheme(
        array $authSchemes,
        CommandInterface $command
    ): void
    {
        $authScheme = $this->resolveAuthScheme($authSchemes);

        $command['@context']['signature_version'] = $authScheme['version'];

        if (isset($authScheme['name'])) {
            $command['@context']['signing_service'] = $authScheme['name'];
        }

        if (isset($authScheme['region'])) {
            $command['@context']['signing_region'] = $authScheme['region'];
        } elseif (isset($authScheme['signingRegionSet'])) {
            $command['@context']['signing_region_set'] = $authScheme['signingRegionSet'];
        }
    }

    /**
     * Returns the first compatible auth scheme in an endpoint object's
     * auth schemes.
     *
     * @param array $authSchemes
     *
     * @return array
     */
    private function resolveAuthScheme(array $authSchemes): array
    {
        $invalidAuthSchemes = [];

        foreach($authSchemes as $authScheme) {
            if ($this->isValidAuthScheme($authScheme['name'])) {
                return $this->normalizeAuthScheme($authScheme);
            }
            $invalidAuthSchemes[$authScheme['name']] = false;
        }

        $invalidAuthSchemesString = '`' . implode(
            '`, `',
            array_keys($invalidAuthSchemes))
            . '`';
        $validAuthSchemesString = '`'
            . implode('`, `', array_keys(
                array_diff_key(self::$validAuthSchemes, $invalidAuthSchemes))
            )
            . '`';
        throw new UnresolvedAuthSchemeException(
            "This operation requests {$invalidAuthSchemesString}"
            . " auth schemes, but the client currently supports {$validAuthSchemesString}."
        );
    }

    /**
     * Normalizes an auth scheme's name, signing region or signing region set
     * to the auth keys recognized by the SDK.
     *
     * @param array $authScheme
     * @return array
     */
    private function normalizeAuthScheme(array $authScheme): array
    {
        /*
            sigv4a will contain a regionSet property. which is guaranteed to be `*`
            for now.  The SigV4 class handles this automatically for now. It seems
            complexity will be added here in the future.
       */
        $normalizedAuthScheme = [];

        if (isset($authScheme['disableDoubleEncoding'])
            && $authScheme['disableDoubleEncoding'] === true
            && $authScheme['name'] !== 'sigv4a'
            && $authScheme['name'] !== 'sigv4-s3express'
        ) {
            $normalizedAuthScheme['version'] = 's3v4';
        } else {
            $normalizedAuthScheme['version'] = self::$validAuthSchemes[$authScheme['name']];
        }

        $normalizedAuthScheme['name'] = $authScheme['signingName'] ?? null;
        $normalizedAuthScheme['region'] = $authScheme['signingRegion'] ?? null;
        $normalizedAuthScheme['signingRegionSet'] = $authScheme['signingRegionSet'] ?? null;

        return $normalizedAuthScheme;
    }

    private function isValidAuthScheme($signatureVersion): bool
    {
        if (isset(self::$validAuthSchemes[$signatureVersion])) {
              if ($signatureVersion === 'sigv4a') {
                  return extension_loaded('awscrt');
              }
              return true;
        }

        return false;
    }

    /**
     * This method tries to resolve an `AccountId` parameter from a resolved identity.
     * We will just perform this operation if the parameter `AccountId` is part of the ruleset parameters and
     * `AccountIdEndpointMode` is not disabled, otherwise, we will ignore it.
     *
     * @return null|string
     */
    private function resolveAccountId(): ?string
    {
        if (isset($this->clientArgs[self::ACCOUNT_ID_ENDPOINT_MODE_PARAM])
            && $this->clientArgs[self::ACCOUNT_ID_ENDPOINT_MODE_PARAM] === 'disabled') {
            return null;
        }

        if (is_null($this->credentialProvider)) {
            return null;
        }

        $identityProviderFn = $this->credentialProvider;
        $identity = $identityProviderFn()->wait();

        return $identity->getAccountId();
    }

    private function appendEndpointMetrics(
        array $providerArgs,
        RulesetEndpoint $endpoint,
        CommandInterface $command
    ): void
    {
        // Resolved AccountId Metric
        if (!empty($providerArgs[self::ACCOUNT_ID_PARAM])) {
            $command->getMetricsBuilder()->append(MetricsBuilder::RESOLVED_ACCOUNT_ID);
        }
        // AccountIdMode Metric
        if(!empty($providerArgs[self::ACCOUNT_ID_ENDPOINT_MODE_PARAM])) {
            $command->getMetricsBuilder()->identifyMetricByValueAndAppend(
                'account_id_endpoint_mode',
                $providerArgs[self::ACCOUNT_ID_ENDPOINT_MODE_PARAM]
            );
        }

        // AccountId Endpoint Metric
        $command->getMetricsBuilder()->identifyMetricByValueAndAppend(
            'account_id_endpoint',
            $endpoint->getUrl()
        );
    }
}
