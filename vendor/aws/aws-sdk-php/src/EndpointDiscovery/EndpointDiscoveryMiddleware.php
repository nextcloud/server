<?php
namespace Aws\EndpointDiscovery;

use Aws\AwsClient;
use Aws\CacheInterface;
use Aws\CommandInterface;
use Aws\Credentials\CredentialsInterface;
use Aws\Exception\AwsException;
use Aws\Exception\UnresolvedEndpointException;
use Aws\LruArrayCache;
use Aws\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class EndpointDiscoveryMiddleware
{
    /**
     * @var CacheInterface
     */
    private static $cache;
    private static $discoveryCooldown = 60;

    private $args;
    private $client;
    private $config;
    private $discoveryTimes = [];
    private $nextHandler;
    private $service;

    public static function wrap(
        $client,
        $args,
        $config
    ) {
        return function (callable $handler) use (
            $client,
            $args,
            $config
        ) {
            return new static(
                $handler,
                $client,
                $args,
                $config
            );
        };
    }

    public function __construct(
        callable $handler,
        AwsClient $client,
        array $args,
        $config
    ) {
        $this->nextHandler = $handler;
        $this->client = $client;
        $this->args = $args;
        $this->service = $client->getApi();
        $this->config = $config;
    }

    public function __invoke(CommandInterface $cmd, RequestInterface $request)
    {
        $nextHandler = $this->nextHandler;
        $op = $this->service->getOperation($cmd->getName())->toArray();

        // Continue only if endpointdiscovery trait is set
        if (isset($op['endpointdiscovery'])) {
            $config = ConfigurationProvider::unwrap($this->config);
            $isRequired = !empty($op['endpointdiscovery']['required']);

            if ($isRequired && !($config->isEnabled())) {
                throw new UnresolvedEndpointException('This operation '
                    . 'requires the use of endpoint discovery, but this has '
                    . 'been disabled in the configuration. Enable endpoint '
                    . 'discovery or use a different operation.');
            }

            // Continue only if enabled by config
            if ($config->isEnabled()) {
                if (isset($op['endpointoperation'])) {
                    throw new UnresolvedEndpointException('This operation is '
                        . 'contradictorily marked both as using endpoint discovery '
                        . 'and being the endpoint discovery operation. Please '
                        . 'verify the accuracy of your model files.');
                }

                // Original endpoint may be used if discovery optional
                $originalUri = $request->getUri();

                $identifiers = $this->getIdentifiers($op);

                $cacheKey = $this->getCacheKey(
                    $this->client->getCredentials()->wait(),
                    $cmd,
                    $identifiers
                );

                // Check/create cache
                if (!isset(self::$cache)) {
                    self::$cache = new LruArrayCache($config->getCacheLimit());
                }

                if (empty($endpointList = self::$cache->get($cacheKey))) {
                    $endpointList = new EndpointList([]);
                }
                $endpoint = $endpointList->getActive();

                // Retrieve endpoints if there is no active endpoint
                if (empty($endpoint)) {
                    try {
                        $endpoint = $this->discoverEndpoint(
                            $cacheKey,
                            $cmd,
                            $identifiers
                        );
                    } catch (\Exception $e) {
                        // Use cached endpoint, expired or active, if any remain
                        $endpoint = $endpointList->getEndpoint();

                        if (empty($endpoint)) {
                            return $this->handleDiscoveryException(
                                $isRequired,
                                $originalUri,
                                $e,
                                $cmd,
                                $request
                            );
                        }
                    }
                }

                $request = $this->modifyRequest($request, $endpoint);

                $g = function ($value) use (
                    $cacheKey,
                    $cmd,
                    $identifiers,
                    $isRequired,
                    $originalUri,
                    $request,
                    &$endpoint,
                    &$g
                ) {
                    if ($value instanceof AwsException
                        && (
                            $value->getAwsErrorCode() == 'InvalidEndpointException'
                            || $value->getStatusCode() == 421
                        )
                    ) {
                        return $this->handleInvalidEndpoint(
                            $cacheKey,
                            $cmd,
                            $identifiers,
                            $isRequired,
                            $originalUri,
                            $request,
                            $value,
                            $endpoint,
                            $g
                        );
                    }
                    
                    return $value;
                };

                return $nextHandler($cmd, $request)->otherwise($g);
            }
        }

        return $nextHandler($cmd, $request);
    }

    private function discoverEndpoint(
        $cacheKey,
        CommandInterface $cmd,
        array $identifiers
    ) {
        $discCmd = $this->getDiscoveryCommand($cmd, $identifiers);
        $this->discoveryTimes[$cacheKey] = time();
        $result = $this->client->execute($discCmd);

        if (isset($result['Endpoints'])) {
            $endpointData = [];
            foreach ($result['Endpoints'] as $datum) {
                $endpointData[$datum['Address']] = time()
                    + ($datum['CachePeriodInMinutes'] * 60);
            }
            $endpointList = new EndpointList($endpointData);
            self::$cache->set($cacheKey, $endpointList);
            return $endpointList->getEndpoint();
        }

        throw new UnresolvedEndpointException('The endpoint discovery operation '
            . 'yielded a response that did not contain properly formatted '
            . 'endpoint data.');
    }

    private function getCacheKey(
        CredentialsInterface $creds,
        CommandInterface $cmd,
        array $identifiers
    ) {
        $key = $this->service->getServiceName() . '_' . $creds->getAccessKeyId();
        if (!empty($identifiers)) {
            $key .= '_' . $cmd->getName();
            foreach ($identifiers as $identifier) {
                $key .= "_{$cmd[$identifier]}";
            }
        }

        return $key;
    }

    private function getDiscoveryCommand(
        CommandInterface $cmd,
        array $identifiers
    ) {
        foreach ($this->service->getOperations() as $op) {
            if (isset($op['endpointoperation'])) {
                $endpointOperation = $op->toArray()['name'];
                break;
            }
        }

        if (!isset($endpointOperation)) {
            throw new UnresolvedEndpointException('This command is set to use '
                . 'endpoint discovery, but no endpoint discovery operation was '
                . 'found. Please verify the accuracy of your model files.');
        }

        $params = [];
        if (!empty($identifiers)) {
            $params['Operation'] = $cmd->getName();
            $params['Identifiers'] = [];
            foreach ($identifiers as $identifier) {
                $params['Identifiers'][$identifier] = $cmd[$identifier];
            }
        }
        $command = $this->client->getCommand($endpointOperation, $params);
        $command->getHandlerList()->appendBuild(
            Middleware::mapRequest(function (RequestInterface $r) {
                return $r->withHeader(
                    'x-amz-api-version',
                    $this->service->getApiVersion()
                );
            }),
            'x-amz-api-version-header'
        );

        return $command;
    }

    private function getIdentifiers(array $operation)
    {
        $inputShape = $this->service->getShapeMap()
            ->resolve($operation['input'])
            ->toArray();
        $identifiers = [];
        foreach ($inputShape['members'] as $key => $member) {
            if (!empty($member['endpointdiscoveryid'])) {
                $identifiers[] = $key;
            }
        }
        return $identifiers;
    }

    private function handleDiscoveryException(
        $isRequired,
        $originalUri,
        \Exception $e,
        CommandInterface $cmd,
        RequestInterface $request
    ) {
        // If no cached endpoints and discovery required,
        // throw exception
        if ($isRequired) {
            $message = 'The endpoint required for this service is currently '
                . 'unable to be retrieved, and your request can not be fulfilled '
                . 'unless you manually specify an endpoint.';
            throw new AwsException(
                $message,
                $cmd,
                [
                    'code' => 'EndpointDiscoveryException',
                    'message' => $message
                ],
                $e
            );
        }

        // If discovery isn't required, use original endpoint
        return $this->useOriginalUri(
            $originalUri,
            $cmd,
            $request
        );
    }

    private function handleInvalidEndpoint(
        $cacheKey,
        $cmd,
        $identifiers,
        $isRequired,
        $originalUri,
        $request,
        $value,
        &$endpoint,
        &$g
    ) {
        $nextHandler = $this->nextHandler;
        $endpointList = self::$cache->get($cacheKey);
        if ($endpointList instanceof EndpointList) {

            // Remove invalid endpoint from cached list
            $endpointList->remove($endpoint);

            // If possible, get another cached endpoint
            $newEndpoint = $endpointList->getEndpoint();
        }
        if (empty($newEndpoint)) {

            // If no more cached endpoints, make discovery call
            // if none made within cooldown for given key
            if (time() - $this->discoveryTimes[$cacheKey]
                < self::$discoveryCooldown
            ) {

                // If no more cached endpoints and it's required,
                // fail with original exception
                if ($isRequired) {
                    return $value;
                }

                // Use original endpoint if not required
                return $this->useOriginalUri(
                    $originalUri,
                    $cmd,
                    $request
                );
            }

            $newEndpoint = $this->discoverEndpoint(
                $cacheKey,
                $cmd,
                $identifiers
            );
        }
        $endpoint = $newEndpoint;
        $request = $this->modifyRequest($request, $endpoint);
        return $nextHandler($cmd, $request)->otherwise($g);
    }

    private function modifyRequest(RequestInterface $request, $endpoint)
    {
        $parsed = $this->parseEndpoint($endpoint);
        if (!empty($request->getHeader('User-Agent'))) {
            $userAgent = $request->getHeader('User-Agent')[0];
            if (strpos($userAgent, 'endpoint-discovery') === false) {
                $userAgent = $userAgent . ' endpoint-discovery';
            }
        } else {
            $userAgent = 'endpoint-discovery';
        }

        return $request
            ->withUri(
                $request->getUri()
                    ->withHost($parsed['host'])
                    ->withPath($parsed['path'])
            )
            ->withHeader('User-Agent', $userAgent);
    }

    /**
     * Parses an endpoint returned from the discovery API into an array with
     * 'host' and 'path' keys.
     *
     * @param $endpoint
     * @return array
     */
    private function parseEndpoint($endpoint)
    {
        $parsed = parse_url($endpoint);

        // parse_url() will correctly parse full URIs with schemes
        if (isset($parsed['host'])) {
            return $parsed;
        }

        // parse_url() will put host & path in 'path' if scheme is not provided
        if (isset($parsed['path'])) {
            $split = explode('/', $parsed['path'], 2);
            $parsed['host'] = $split[0];
            if (isset($split[1])) {
                if (substr($split[1], 0 , 1) !== '/') {
                    $split[1] = '/' . $split[1];
                }
                $parsed['path'] = $split[1];
            } else {
                $parsed['path'] = '';
            }
            return $parsed;
        }

        throw new UnresolvedEndpointException("The supplied endpoint '"
            . "{$endpoint}' is invalid.");
    }

    private function useOriginalUri(
        UriInterface $uri,
        CommandInterface $cmd,
        RequestInterface $request
    ) {
        $nextHandler = $this->nextHandler;
        $endpoint = $uri->getHost() . $uri->getPath();
        $request = $this->modifyRequest(
            $request,
            $endpoint
        );
        return $nextHandler($cmd, $request);
    }
}
