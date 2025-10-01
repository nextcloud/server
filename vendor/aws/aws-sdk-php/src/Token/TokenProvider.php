<?php
namespace Aws\Token;

use Aws;
use Aws\Api\DateTimeResult;
use Aws\CacheInterface;
use Aws\Exception\TokenException;
use GuzzleHttp\Promise;

/**
 * Token providers are functions that accept no arguments and return a
 * promise that is fulfilled with an {@see \Aws\Token\TokenInterface}
 * or rejected with an {@see \Aws\Exception\TokenException}.
 *
 * <code>
 * use Aws\Token\TokenProvider;
 * $provider = TokenProvider::defaultProvider();
 * // Returns a TokenInterface or throws.
 * $token = $provider()->wait();
 * </code>
 *
 * Token providers can be composed to create a token using conditional
 * logic that can create different tokens in different environments. You
 * can compose multiple providers into a single provider using
 * {@see Aws\Token\TokenProvider::chain}. This function accepts
 * providers as variadic arguments and returns a new function that will invoke
 * each provider until a token is successfully returned.
 */
class TokenProvider
{
    use ParsesIniTrait;
    const ENV_PROFILE = 'AWS_PROFILE';

    /**
     * Create a default token provider tha checks for cached a SSO token from
     * the CLI
     *
     * This provider is automatically wrapped in a memoize function that caches
     * previously provided tokens.
     *
     * @param array $config Optional array of token provider options.
     *
     * @return callable
     */
    public static function defaultProvider(array $config = [])
    {

        $cacheable = [
            'sso',
        ];

        $defaultChain = [];

        if (
            !isset($config['use_aws_shared_config_files'])
            || $config['use_aws_shared_config_files'] !== false
        ) {
            $profileName = getenv(self::ENV_PROFILE) ?: 'default';
            $defaultChain['sso'] = self::sso(
                $profileName,
                self::getHomeDir() . '/.aws/config',
                $config
            );
        }

        if (isset($config['token'])
            && $config['token'] instanceof CacheInterface
        ) {
            foreach ($cacheable as $provider) {
                if (isset($defaultChain[$provider])) {
                    $defaultChain[$provider] = self::cache(
                        $defaultChain[$provider],
                        $config['token'],
                        'aws_cached_' . $provider . '_token'
                    );
                }
            }
        }

        return self::memoize(
            call_user_func_array(
                [TokenProvider::class, 'chain'],
                array_values($defaultChain)
            )
        );
    }

    /**
     * Create a token provider function from a static token.
     *
     * @param TokenInterface $token
     *
     * @return callable
     */
    public static function fromToken(TokenInterface $token)
    {
        $promise = Promise\Create::promiseFor($token);

        return function () use ($promise) {
            return $promise;
        };
    }

    /**
     * Creates an aggregate token provider that invokes the provided
     * variadic providers one after the other until a provider returns
     * a token.
     *
     * @return callable
     */
    public static function chain()
    {
        $links = func_get_args();
        //Common use case for when aws_shared_config_files is false
        if (empty($links)) {
            return function () {
                return Promise\Create::promiseFor(false);
            };
        }

        return function () use ($links) {
            /** @var callable $parent */
            $parent = array_shift($links);
            $promise = $parent();
            while ($next = array_shift($links)) {
                $promise = $promise->otherwise($next);
            }
            return $promise;
        };
    }

    /**
     * Wraps a token provider and caches a previously provided token.
     * Ensures that cached tokens are refreshed when they expire.
     *
     * @param callable $provider Token provider function to wrap.
     * @return callable
     */
    public static function memoize(callable $provider)
    {
        return function () use ($provider) {
            static $result;
            static $isConstant;

            // Constant tokens will be returned constantly.
            if ($isConstant) {
                return $result;
            }

            // Create the initial promise that will be used as the cached value
            // until it expires.
            if (null === $result) {
                $result = $provider();
            }

            // Return a token that could expire and refresh when needed.
            return $result
                ->then(function (TokenInterface $token) use ($provider, &$isConstant, &$result) {
                    // Determine if the token is constant.
                    if (!$token->getExpiration()) {
                        $isConstant = true;
                        return $token;
                    }

                    if (!$token->isExpired()) {
                        return $token;
                    }
                    return $result = $provider();
                })
                ->otherwise(function($reason) use (&$result) {
                    // Cleanup rejected promise.
                    $result = null;
                    return Promise\Create::promiseFor(null);
                });
        };
    }

    /**
     * Wraps a token provider and saves provided token in an
     * instance of Aws\CacheInterface. Forwards calls when no token found
     * in cache and updates cache with the results.
     *
     * @param callable $provider Token provider function to wrap
     * @param CacheInterface $cache Cache to store the token
     * @param string|null $cacheKey (optional) Cache key to use
     *
     * @return callable
     */
    public static function cache(
        callable $provider,
        CacheInterface $cache,
        $cacheKey = null
    ) {
        $cacheKey = $cacheKey ?: 'aws_cached_token';

        return function () use ($provider, $cache, $cacheKey) {
            $found = $cache->get($cacheKey);
            if (is_array($found) && isset($found['token'])) {
                $foundToken = $found['token'];
                if ($foundToken instanceof TokenInterface) {
                    if (!$foundToken->isExpired()) {
                        return Promise\Create::promiseFor($foundToken);
                    }
                    if (isset($found['refreshMethod']) && is_callable($found['refreshMethod'])) {
                        return Promise\Create::promiseFor($found['refreshMethod']());
                    }
                }
            }

            return $provider()
                ->then(function (TokenInterface $token) use (
                    $cache,
                    $cacheKey
                ) {
                    $cache->set(
                        $cacheKey,
                        $token,
                        null === $token->getExpiration() ?
                            0 : $token->getExpiration() - time()
                    );

                    return $token;
                });
        };
    }

    /**
     * Gets profiles from the ~/.aws/config ini file
     */
    private static function loadDefaultProfiles() {
        $profiles = [];
        $configFile = self::getHomeDir() . '/.aws/config';

        if (file_exists($configFile)) {
            $configProfileData = \Aws\parse_ini_file($configFile, true, INI_SCANNER_RAW);
            foreach ($configProfileData as $name => $profile) {
                // standardize config profile names
                $name = str_replace('profile ', '', $name);
                if (!isset($profiles[$name])) {
                    $profiles[$name] = $profile;
                }
            }
        }

        return $profiles;
    }

    private static function reject($msg)
    {
        return new Promise\RejectedPromise(new TokenException($msg));
    }

    /**
     * Token provider that creates a token from cached sso credentials
     *
     * @param string $profileName the name of the ini profile name
     * @param string $filename the location of the ini file
     * @param array $config configuration options
     *
     * @return SsoTokenProvider
     * @see Aws\Token\SsoTokenProvider for $config details.
     */
    public static function sso($profileName, $filename, $config = [])
    {
        $ssoClient = isset($config['ssoClient']) ? $config['ssoClient'] : null;

        return new SsoTokenProvider($profileName, $filename, $ssoClient);
    }
}

