<?php
namespace Aws\Endpoint;

use Aws\Exception\UnresolvedEndpointException;

/**
 * Endpoint providers.
 *
 * An endpoint provider is a function that accepts a hash of endpoint options,
 * including but not limited to "service" and "region" key value pairs. The
 * endpoint provider function returns a hash of endpoint data, which MUST
 * include an "endpoint" key value pair that represents the resolved endpoint
 * or NULL if an endpoint cannot be determined.
 *
 * You can wrap your calls to an endpoint provider with the
 * {@see EndpointProvider::resolve} function to ensure that an endpoint hash is
 * created. If an endpoint hash is not created, then the resolve() function
 * will throw an {@see Aws\Exception\UnresolvedEndpointException}.
 *
 *     use Aws\Endpoint\EndpointProvider;
 *     $provider = EndpointProvider::defaultProvider();
 *     // Returns an array or NULL.
 *     $endpoint = $provider(['service' => 'ec2', 'region' => 'us-west-2']);
 *     // Returns an endpoint array or throws.
 *     $endpoint = EndpointProvider::resolve($provider, [
 *         'service' => 'ec2',
 *         'region'  => 'us-west-2'
 *     ]);
 *
 * You can compose multiple providers into a single provider using
 * {@see Aws\or_chain}. This function accepts providers as arguments and
 * returns a new function that will invoke each provider until a non-null value
 * is returned.
 *
 *     $a = function (array $args) {
 *         if ($args['region'] === 'my-test-region') {
 *             return ['endpoint' => 'http://localhost:123/api'];
 *         }
 *     };
 *     $b = EndpointProvider::defaultProvider();
 *     $c = \Aws\or_chain($a, $b);
 *     $config = ['service' => 'ec2', 'region' => 'my-test-region'];
 *     $res = $c($config);  // $a handles this.
 *     $config['region'] = 'us-west-2';
 *     $res = $c($config); // $b handles this.
 */
class EndpointProvider
{
    /**
     * Resolves and endpoint provider and ensures a non-null return value.
     *
     * @param callable $provider Provider function to invoke.
     * @param array    $args     Endpoint arguments to pass to the provider.
     *
     * @return array
     * @throws UnresolvedEndpointException
     */
    public static function resolve(callable $provider, array $args = [])
    {
        $result = $provider($args);
        if (is_array($result)) {
            return $result;
        }

        throw new UnresolvedEndpointException(
            'Unable to resolve an endpoint using the provider arguments: '
            . json_encode($args) . '. Note: you can provide an "endpoint" '
            . 'option to a client constructor to bypass invoking an endpoint '
            . 'provider.');
    }

    /**
     * Creates and returns the default SDK endpoint provider.
     *
     * @deprecated Use an instance of \Aws\Endpoint\Partition instead.
     *
     * @return callable
     */
    public static function defaultProvider()
    {
        return PartitionEndpointProvider::defaultProvider();
    }

    /**
     * Creates and returns an endpoint provider that uses patterns from an
     * array.
     *
     * @param array $patterns Endpoint patterns
     *
     * @return callable
     */
    public static function patterns(array $patterns)
    {
        return new PatternEndpointProvider($patterns);
    }
}
