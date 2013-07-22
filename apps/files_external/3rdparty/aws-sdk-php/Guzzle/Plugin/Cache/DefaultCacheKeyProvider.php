<?php

namespace Guzzle\Plugin\Cache;

use Guzzle\Http\Message\RequestInterface;

\Guzzle\Common\Version::warn('Guzzle\Plugin\Cache\DefaultCacheKeyProvider is no longer used');

/**
 * @deprecated This class is no longer used
 * @codeCoverageIgnore
 */
class DefaultCacheKeyProvider implements CacheKeyProviderInterface
{
    public function getCacheKey(RequestInterface $request)
    {
        // See if the key has already been calculated
        $key = $request->getParams()->get(self::CACHE_KEY);

        if (!$key) {

            $cloned = clone $request;
            $cloned->removeHeader('Cache-Control');

            // Check to see how and if the key should be filtered
            foreach (explode(';', $request->getParams()->get(self::CACHE_KEY_FILTER)) as $part) {
                $pieces = array_map('trim', explode('=', $part));
                if (isset($pieces[1])) {
                    foreach (array_map('trim', explode(',', $pieces[1])) as $remove) {
                        if ($pieces[0] == 'header') {
                            $cloned->removeHeader($remove);
                        } elseif ($pieces[0] == 'query') {
                            $cloned->getQuery()->remove($remove);
                        }
                    }
                }
            }

            $raw = (string) $cloned;
            $key = 'GZ' . md5($raw);
            $request->getParams()->set(self::CACHE_KEY, $key)->set(self::CACHE_KEY_RAW, $raw);
        }

        return $key;
    }
}
