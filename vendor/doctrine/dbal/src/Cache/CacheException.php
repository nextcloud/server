<?php

namespace Doctrine\DBAL\Cache;

use Doctrine\DBAL\Exception;

class CacheException extends Exception
{
    /** @return CacheException */
    public static function noCacheKey()
    {
        return new self('No cache key was set.');
    }

    /** @return CacheException */
    public static function noResultDriverConfigured()
    {
        return new self('Trying to cache a query but no result driver is configured.');
    }
}
