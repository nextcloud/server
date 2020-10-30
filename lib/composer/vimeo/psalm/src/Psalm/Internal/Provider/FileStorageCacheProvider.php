<?php
namespace Psalm\Internal\Provider;

use function array_merge;
use const DIRECTORY_SEPARATOR;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function get_class;
use function igbinary_serialize;
use function igbinary_unserialize;
use function is_dir;
use function mkdir;
use Psalm\Config;
use Psalm\Storage\FileStorage;
use function serialize;
use function sha1;
use function strtolower;
use function unlink;
use function unserialize;

/**
 * @internal
 */
class FileStorageCacheProvider
{
    /**
     * @var string
     */
    private $modified_timestamps = '';

    /**
     * @var Config
     */
    private $config;

    private const FILE_STORAGE_CACHE_DIRECTORY = 'file_cache';

    public function __construct(Config $config)
    {
        $this->config = $config;

        $storage_dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Storage' . DIRECTORY_SEPARATOR;

        $dependent_files = [
            $storage_dir . 'FileStorage.php',
            $storage_dir . 'FunctionLikeStorage.php',
            $storage_dir . 'ClassLikeStorage.php',
            $storage_dir . 'MethodStorage.php',
            $storage_dir . 'FunctionLikeParameter.php',
        ];

        if ($config->after_visit_classlikes) {
            $dependent_files = array_merge($dependent_files, $config->plugin_paths);
        }

        foreach ($dependent_files as $dependent_file_path) {
            if (!file_exists($dependent_file_path)) {
                throw new \UnexpectedValueException($dependent_file_path . ' must exist');
            }

            $this->modified_timestamps .= ' ' . filemtime($dependent_file_path);
        }

        $this->modified_timestamps .= $this->config->hash;
    }

    public function writeToCache(FileStorage $storage, string $file_contents): void
    {
        $file_path = strtolower($storage->file_path);
        $cache_location = $this->getCacheLocationForPath($file_path, true);
        $storage->hash = $this->getCacheHash($file_path, $file_contents);

        if ($this->config->use_igbinary) {
            file_put_contents($cache_location, igbinary_serialize($storage));
        } else {
            file_put_contents($cache_location, serialize($storage));
        }
    }

    public function getLatestFromCache(string $file_path, string $file_contents): ?FileStorage
    {
        $file_path = strtolower($file_path);
        $cached_value = $this->loadFromCache($file_path);

        if (!$cached_value) {
            return null;
        }

        $cache_hash = $this->getCacheHash($file_path, $file_contents);

        /** @psalm-suppress TypeDoesNotContainType */
        if (@get_class($cached_value) === '__PHP_Incomplete_Class'
            || $cache_hash !== $cached_value->hash
        ) {
            $this->removeCacheForFile($file_path);

            return null;
        }

        return $cached_value;
    }

    public function removeCacheForFile(string $file_path): void
    {
        $cache_path = $this->getCacheLocationForPath($file_path);

        if (file_exists($cache_path)) {
            unlink($cache_path);
        }
    }

    private function getCacheHash(string $file_path, string $file_contents): string
    {
        return sha1(strtolower($file_path) . ' ' . $file_contents . $this->modified_timestamps);
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    private function loadFromCache(string $file_path): ?FileStorage
    {
        $cache_location = $this->getCacheLocationForPath($file_path);

        if (file_exists($cache_location)) {
            if ($this->config->use_igbinary) {
                $storage = igbinary_unserialize((string)file_get_contents($cache_location));

                if ($storage instanceof FileStorage) {
                    return $storage;
                }

                return null;
            }

            $storage = unserialize((string)file_get_contents($cache_location));

            if ($storage instanceof FileStorage) {
                return $storage;
            }

            return null;
        }

        return null;
    }

    private function getCacheLocationForPath(string $file_path, bool $create_directory = false): string
    {
        $root_cache_directory = $this->config->getCacheDirectory();

        if (!$root_cache_directory) {
            throw new \UnexpectedValueException('No cache directory defined');
        }

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_STORAGE_CACHE_DIRECTORY;

        if ($create_directory && !is_dir($parser_cache_directory)) {
            mkdir($parser_cache_directory, 0777, true);
        }

        return $parser_cache_directory
            . DIRECTORY_SEPARATOR
            . sha1($file_path)
            . ($this->config->use_igbinary ? '-igbinary' : '');
    }
}
