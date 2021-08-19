<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp;

use Exception;
use ScssPhp\ScssPhp\Version;

/**
 * The scss cache manager.
 *
 * In short:
 *
 * allow to put in cache/get from cache a generic result from a known operation on a generic dataset,
 * taking in account options that affects the result
 *
 * The cache manager is agnostic about data format and only the operation is expected to be described by string
 */

/**
 * SCSS cache
 *
 * @author Cedric Morin <cedric@yterium.com>
 *
 * @internal
 */
class Cache
{
    const CACHE_VERSION = 1;

    /**
     * directory used for storing data
     *
     * @var string|false
     */
    public static $cacheDir = false;

    /**
     * prefix for the storing data
     *
     * @var string
     */
    public static $prefix = 'scssphp_';

    /**
     * force a refresh : 'once' for refreshing the first hit on a cache only, true to never use the cache in this hit
     *
     * @var bool|string
     */
    public static $forceRefresh = false;

    /**
     * specifies the number of seconds after which data cached will be seen as 'garbage' and potentially cleaned up
     *
     * @var int
     */
    public static $gcLifetime = 604800;

    /**
     * array of already refreshed cache if $forceRefresh==='once'
     *
     * @var array<string, bool>
     */
    protected static $refreshed = [];

    /**
     * Constructor
     *
     * @param array $options
     *
     * @phpstan-param array{cacheDir?: string, prefix?: string, forceRefresh?: string} $options
     */
    public function __construct($options)
    {
        // check $cacheDir
        if (isset($options['cacheDir'])) {
            self::$cacheDir = $options['cacheDir'];
        }

        if (empty(self::$cacheDir)) {
            throw new Exception('cacheDir not set');
        }

        if (isset($options['prefix'])) {
            self::$prefix = $options['prefix'];
        }

        if (empty(self::$prefix)) {
            throw new Exception('prefix not set');
        }

        if (isset($options['forceRefresh'])) {
            self::$forceRefresh = $options['forceRefresh'];
        }

        self::checkCacheDir();
    }

    /**
     * Get the cached result of $operation on $what,
     * which is known as dependant from the content of $options
     *
     * @param string   $operation    parse, compile...
     * @param mixed    $what         content key (e.g., filename to be treated)
     * @param array    $options      any option that affect the operation result on the content
     * @param int|null $lastModified last modified timestamp
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getCache($operation, $what, $options = [], $lastModified = null)
    {
        $fileCache = self::$cacheDir . self::cacheName($operation, $what, $options);

        if (
            ((self::$forceRefresh === false) || (self::$forceRefresh === 'once' &&
            isset(self::$refreshed[$fileCache]))) && file_exists($fileCache)
        ) {
            $cacheTime = filemtime($fileCache);

            if (
                (\is_null($lastModified) || $cacheTime > $lastModified) &&
                $cacheTime + self::$gcLifetime > time()
            ) {
                $c = file_get_contents($fileCache);
                $c = unserialize($c);

                if (\is_array($c) && isset($c['value'])) {
                    return $c['value'];
                }
            }
        }

        return null;
    }

    /**
     * Put in cache the result of $operation on $what,
     * which is known as dependant from the content of $options
     *
     * @param string $operation
     * @param mixed  $what
     * @param mixed  $value
     * @param array  $options
     *
     * @return void
     */
    public function setCache($operation, $what, $value, $options = [])
    {
        $fileCache = self::$cacheDir . self::cacheName($operation, $what, $options);

        $c = ['value' => $value];
        $c = serialize($c);

        file_put_contents($fileCache, $c);

        if (self::$forceRefresh === 'once') {
            self::$refreshed[$fileCache] = true;
        }
    }

    /**
     * Get the cache name for the caching of $operation on $what,
     * which is known as dependant from the content of $options
     *
     * @param string $operation
     * @param mixed  $what
     * @param array  $options
     *
     * @return string
     */
    private static function cacheName($operation, $what, $options = [])
    {
        $t = [
          'version' => self::CACHE_VERSION,
          'scssphpVersion' => Version::VERSION,
          'operation' => $operation,
          'what' => $what,
          'options' => $options
        ];

        $t = self::$prefix
          . sha1(json_encode($t))
          . ".$operation"
          . ".scsscache";

        return $t;
    }

    /**
     * Check that the cache dir exists and is writeable
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function checkCacheDir()
    {
        self::$cacheDir = str_replace('\\', '/', self::$cacheDir);
        self::$cacheDir = rtrim(self::$cacheDir, '/') . '/';

        if (! is_dir(self::$cacheDir)) {
            throw new Exception('Cache directory doesn\'t exist: ' . self::$cacheDir);
        }

        if (! is_writable(self::$cacheDir)) {
            throw new Exception('Cache directory isn\'t writable: ' . self::$cacheDir);
        }
    }

    /**
     * Delete unused cached files
     *
     * @return void
     */
    public static function cleanCache()
    {
        static $clean = false;

        if ($clean || empty(self::$cacheDir)) {
            return;
        }

        $clean = true;

        // only remove files with extensions created by SCSSPHP Cache
        // css files removed based on the list files
        $removeTypes = ['scsscache' => 1];

        $files = scandir(self::$cacheDir);

        if (! $files) {
            return;
        }

        $checkTime = time() - self::$gcLifetime;

        foreach ($files as $file) {
            // don't delete if the file wasn't created with SCSSPHP Cache
            if (strpos($file, self::$prefix) !== 0) {
                continue;
            }

            $parts = explode('.', $file);
            $type = array_pop($parts);

            if (! isset($removeTypes[$type])) {
                continue;
            }

            $fullPath = self::$cacheDir . $file;
            $mtime = filemtime($fullPath);

            // don't delete if it's a relatively new file
            if ($mtime > $checkTime) {
                continue;
            }

            unlink($fullPath);
        }
    }
}
