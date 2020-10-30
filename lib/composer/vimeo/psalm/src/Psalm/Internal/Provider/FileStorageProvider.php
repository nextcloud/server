<?php
namespace Psalm\Internal\Provider;

use function array_merge;
use Psalm\Storage\FileStorage;
use function strtolower;

/**
 * @internal
 */
class FileStorageProvider
{
    /**
     * A list of data useful to analyse files
     * Storing this statically is much faster (at least in PHP 7.2.1)
     *
     * @var array<string, FileStorage>
     */
    private static $storage = [];

    /**
     * A list of data useful to analyse new files
     * Storing this statically is much faster (at least in PHP 7.2.1)
     *
     * @var array<string, FileStorage>
     */
    private static $new_storage = [];

    /**
     * @var ?FileStorageCacheProvider
     */
    public $cache;

    public function __construct(?FileStorageCacheProvider $cache = null)
    {
        $this->cache = $cache;
    }

    public function get(string $file_path): FileStorage
    {
        $file_path = strtolower($file_path);

        if (!isset(self::$storage[$file_path])) {
            throw new \InvalidArgumentException('Could not get file storage for ' . $file_path);
        }

        return self::$storage[$file_path];
    }

    public function remove(string $file_path): void
    {
        unset(self::$storage[strtolower($file_path)]);
    }

    public function has(string $file_path, ?string $file_contents = null): bool
    {
        $file_path = strtolower($file_path);

        if (isset(self::$storage[$file_path])) {
            return true;
        }

        if ($file_contents === null) {
            return false;
        }

        if (!$this->cache) {
            return false;
        }

        $cached_value = $this->cache->getLatestFromCache($file_path, $file_contents);

        if (!$cached_value) {
            return false;
        }

        self::$storage[$file_path] = $cached_value;
        self::$new_storage[$file_path] = $cached_value;

        return true;
    }

    /**
     * @return array<string, FileStorage>
     */
    public function getAll(): array
    {
        return self::$storage;
    }

    /**
     * @return array<string, FileStorage>
     */
    public function getNew(): array
    {
        return self::$new_storage;
    }

    /**
     * @param array<string, FileStorage> $more
     *
     */
    public function addMore(array $more): void
    {
        self::$new_storage = array_merge(self::$new_storage, $more);
        self::$storage = array_merge(self::$storage, $more);
    }

    public function create(string $file_path): FileStorage
    {
        $file_path_lc = strtolower($file_path);

        $storage = new FileStorage($file_path);
        self::$storage[$file_path_lc] = $storage;
        self::$new_storage[$file_path_lc] = $storage;

        return $storage;
    }

    public static function deleteAll(): void
    {
        self::$storage = [];
    }

    public static function populated(): void
    {
        self::$new_storage = [];
    }
}
