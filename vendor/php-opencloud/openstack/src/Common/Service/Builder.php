<?php

declare(strict_types=1);

namespace OpenStack\Common\Service;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use OpenStack\Common\Auth\IdentityService;
use OpenStack\Common\Transport\HandlerStackFactory;
use OpenStack\Common\Transport\Utils;

/**
 * A Builder for easily creating OpenStack services.
 */
class Builder
{
    /**
     * Global options that will be applied to every service created by this builder.
     *
     * @var array
     */
    private $globalOptions = [];

    /** @var string */
    private $rootNamespace;

    /**
     * Defaults that will be applied to options if no values are provided by the user.
     *
     * @var array
     */
    private $defaults = ['urlType' => 'publicURL'];

    /**
     * @param array  $globalOptions options that will be applied to every service created by this builder.
     *                              Eventually they will be merged (and if necessary overridden) by the
     *                              service-specific options passed in
     * @param string $rootNamespace API classes' root namespace
     */
    public function __construct(array $globalOptions = [], $rootNamespace = 'OpenStack')
    {
        $this->globalOptions = $globalOptions;
        $this->rootNamespace = $rootNamespace;
    }

    private function getClasses($namespace)
    {
        $namespace = $this->rootNamespace.'\\'.$namespace;
        $classes   = [$namespace.'\\Api', $namespace.'\\Service'];

        foreach ($classes as $class) {
            if (!class_exists($class)) {
                throw new \RuntimeException(sprintf('%s does not exist', $class));
            }
        }

        return $classes;
    }

    /**
     * This method will return an OpenStack service ready fully built and ready for use. There is
     * some initial setup that may prohibit users from directly instantiating the service class
     * directly - this setup includes the configuration of the HTTP client's base URL, and the
     * attachment of an authentication handler.
     *
     * @param string $namespace      The namespace of the service
     * @param array  $serviceOptions The service-specific options to use
     */
    public function createService(string $namespace, array $serviceOptions = []): ServiceInterface
    {
        $options = $this->mergeOptions($serviceOptions);

        $this->stockAuthHandler($options);
        $this->stockHttpClient($options, $namespace);

        [$apiClass, $serviceClass] = $this->getClasses($namespace);

        return new $serviceClass($options['httpClient'], new $apiClass());
    }

    private function stockHttpClient(array &$options, string $serviceName): void
    {
        if (!isset($options['httpClient']) || !($options['httpClient'] instanceof ClientInterface)) {
            if (false !== stripos($serviceName, 'identity')) {
                $baseUrl = $options['authUrl'];
                $token   = null;
            } else {
                [$token, $baseUrl] = $options['identityService']->authenticate($options);
            }

            $stack        = HandlerStackFactory::createWithOptions(array_merge($options, ['token' => $token]));
            $microVersion = $options['microVersion'] ?? null;

            $options['httpClient'] = $this->httpClient($baseUrl, $stack, $options['catalogType'], $microVersion);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    private function stockAuthHandler(array &$options): void
    {
        if (!isset($options['authHandler'])) {
            $options['authHandler'] = function () use ($options) {
                return $options['identityService']->authenticate($options)[0];
            };
        }
    }

    private function httpClient(string $baseUrl, HandlerStack $stack, ?string $serviceType = null, ?string $microVersion = null): ClientInterface
    {
        $clientOptions = [
            'base_uri' => Utils::normalizeUrl($baseUrl),
            'handler'  => $stack,
        ];

        if ($microVersion && $serviceType) {
            $clientOptions['headers']['OpenStack-API-Version'] = sprintf('%s %s', $serviceType, $microVersion);
        }

        if (isset($this->globalOptions['requestOptions'])) {
            $clientOptions = array_merge($this->globalOptions['requestOptions'], $clientOptions);
        }

        return new Client($clientOptions);
    }

    private function mergeOptions(array $serviceOptions): array
    {
        $options = array_merge($this->defaults, $this->globalOptions, $serviceOptions);

        if (!isset($options['authUrl'])) {
            throw new \InvalidArgumentException('"authUrl" is a required option');
        }

        if (!isset($options['identityService']) || !($options['identityService'] instanceof IdentityService)) {
            throw new \InvalidArgumentException(sprintf('"identityService" must be specified and implement %s', IdentityService::class));
        }

        return $options;
    }
}
