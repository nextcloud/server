<?php
namespace Aws\Signature;

use Aws\Exception\UnresolvedSignatureException;
use Aws\Token\BearerTokenAuthorization;

/**
 * Signature providers.
 *
 * A signature provider is a function that accepts a version, service, and
 * region and returns a {@see SignatureInterface} object on success or NULL if
 * no signature can be created from the provided arguments.
 *
 * You can wrap your calls to a signature provider with the
 * {@see SignatureProvider::resolve} function to ensure that a signature object
 * is created. If a signature object is not created, then the resolve()
 * function will throw a {@see Aws\Exception\UnresolvedSignatureException}.
 *
 *     use Aws\Signature\SignatureProvider;
 *     $provider = SignatureProvider::defaultProvider();
 *     // Returns a SignatureInterface or NULL.
 *     $signer = $provider('v4', 's3', 'us-west-2');
 *     // Returns a SignatureInterface or throws.
 *     $signer = SignatureProvider::resolve($provider, 'no', 's3', 'foo');
 *
 * You can compose multiple providers into a single provider using
 * {@see Aws\or_chain}. This function accepts providers as arguments and
 * returns a new function that will invoke each provider until a non-null value
 * is returned.
 *
 *     $a = SignatureProvider::defaultProvider();
 *     $b = function ($version, $service, $region) {
 *         if ($version === 'foo') {
 *             return new MyFooSignature();
 *         }
 *     };
 *     $c = \Aws\or_chain($a, $b);
 *     $signer = $c('v4', 'abc', '123');     // $a handles this.
 *     $signer = $c('foo', 'abc', '123');    // $b handles this.
 *     $nullValue = $c('???', 'abc', '123'); // Neither can handle this.
 */
class SignatureProvider
{
    private static $s3v4SignedServices = [
        's3' => true,
        's3control' => true,
        's3-outposts' => true,
        's3-object-lambda' => true,
        's3express' => true
    ];

    /**
     * Resolves and signature provider and ensures a non-null return value.
     *
     * @param callable $provider Provider function to invoke.
     * @param string   $version  Signature version.
     * @param string   $service  Service name.
     * @param string   $region   Region name.
     *
     * @return SignatureInterface
     * @throws UnresolvedSignatureException
     */
    public static function resolve(callable $provider, $version, $service, $region)
    {
        $result = $provider($version, $service, $region);
        if ($result instanceof SignatureInterface
            || $result instanceof BearerTokenAuthorization
        ) {
            return $result;
        }

        throw new UnresolvedSignatureException(
            "Unable to resolve a signature for $version/$service/$region.\n"
            . "Valid signature versions include v4 and anonymous."
        );
    }

    /**
     * Default SDK signature provider.
     *
     * @return callable
     */
    public static function defaultProvider()
    {
        return self::memoize(self::version());
    }

    /**
     * Creates a signature provider that caches previously created signature
     * objects. The computed cache key is the concatenation of the version,
     * service, and region.
     *
     * @param callable $provider Signature provider to wrap.
     *
     * @return callable
     */
    public static function memoize(callable $provider)
    {
        $cache = [];
        return function ($version, $service, $region) use (&$cache, $provider) {
            $key = "($version)($service)($region)";
            if (!isset($cache[$key])) {
                $cache[$key] = $provider($version, $service, $region);
            }
            return $cache[$key];
        };
    }

    /**
     * Creates signature objects from known signature versions.
     *
     * This provider currently recognizes the following signature versions:
     *
     * - v4: Signature version 4.
     * - anonymous: Does not sign requests.
     *
     * @return callable
     */
    public static function version()
    {
        return function ($version, $service, $region) {
            switch ($version) {
                case 'v4-s3express':
                    return new S3ExpressSignature($service, $region);
                case 's3v4':
                case 'v4':
                    return !empty(self::$s3v4SignedServices[$service])
                        ? new S3SignatureV4($service, $region)
                        : new SignatureV4($service, $region);
                case 'v4a':
                    return !empty(self::$s3v4SignedServices[$service])
                        ? new S3SignatureV4($service, $region, ['use_v4a' => true])
                        : new SignatureV4($service, $region, ['use_v4a' => true]);
                case 'v4-unsigned-body':
                    return !empty(self::$s3v4SignedServices[$service])
                    ? new S3SignatureV4($service, $region, ['unsigned-body' => 'true'])
                    : new SignatureV4($service, $region, ['unsigned-body' => 'true']);
                case 'bearer':
                    return new BearerTokenAuthorization();
                case 'anonymous':
                    return new AnonymousSignature();
                default:
                    return null;
            }
        };
    }
}
