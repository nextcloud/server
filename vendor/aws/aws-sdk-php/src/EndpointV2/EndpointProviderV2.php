<?php

namespace Aws\EndpointV2;

use Aws\EndpointV2\Ruleset\Ruleset;
use Aws\EndpointV2\Ruleset\RulesetEndpoint;
use Aws\Exception\UnresolvedEndpointException;
use Aws\LruArrayCache;

/**
 * Given a service's Ruleset and client-provided input parameters, provides
 * either an object reflecting the properties of a resolved endpoint,
 * or throws an error.
 */
class EndpointProviderV2
{
    /** @var Ruleset */
    private $ruleset;

    /** @var LruArrayCache */
    private $cache;

    public function __construct(array $ruleset, array $partitions)
    {
        $this->ruleset = new Ruleset($ruleset, $partitions);
        $this->cache = new LruArrayCache(100);
    }

    /**
     * @return Ruleset
     */
    public function getRuleset()
    {
        return $this->ruleset;
    }

    /**
     * Given a Ruleset and input parameters, determines the correct endpoint
     * or an error to be thrown for a given request.
     *
     * @return RulesetEndpoint
     * @throws UnresolvedEndpointException
     */
    public function resolveEndpoint(array $inputParameters)
    {
        $hashedParams = $this->hashInputParameters($inputParameters);
        $match = $this->cache->get($hashedParams);

        if (!is_null($match)) {
            return $match;
        }

        $endpoint = $this->ruleset->evaluate($inputParameters);
        if ($endpoint === false) {
            throw new UnresolvedEndpointException(
                'Unable to resolve an endpoint using the provider arguments: '
                . json_encode($inputParameters)
            );
        }
        $this->cache->set($hashedParams, $endpoint);

        return $endpoint;
    }

    private function hashInputParameters($inputParameters)
    {
        return md5(serialize($inputParameters));
    }
}
