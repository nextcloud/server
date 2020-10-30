<?php
namespace Psalm\Internal\Provider;

use Psalm\Config;
use Psalm\Storage\ClassLikeStorage;
use function dirname;
use const DIRECTORY_SEPARATOR;
use function array_merge;
use function file_exists;
use function filemtime;
use function strtolower;
use function file_put_contents;
use function igbinary_serialize;
use function serialize;
use function get_class;
use function unlink;
use function sha1;
use function igbinary_unserialize;
use function file_get_contents;
use function unserialize;
use function is_dir;
use function mkdir;

/**
 * @internal
 */
class ClassLikeStorageCacheProvider
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $modified_timestamps = '';

    private const CLASS_CACHE_DIRECTORY = 'class_cache';

    public function __construct(Config $config)
    {
        $this->config = $config;

        $storage_dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Storage' . DIRECTORY_SEPARATOR;

        $dependent_files = [
            $storage_dir . 'FileStorage.php',
            $storage_dir . 'FunctionLikeStorage.php',
            $storage_dir . 'ClassLikeStorage.php',
            $storage_dir . 'MethodStorage.php',
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

    public function writeToCache(ClassLikeStorage $storage, ?string $file_path, ?string $file_contents): void
    {
        $fq_classlike_name_lc = strtolower($storage->name);

        $cache_location = $this->getCacheLocationForClass($fq_classlike_name_lc, $file_path, true);
        $storage->hash = $this->getCacheHash($file_path, $file_contents);

        if ($this->config->use_igbinary) {
            file_put_contents($cache_location, igbinary_serialize($storage));
        } else {
            file_put_contents($cache_location, serialize($storage));
        }
    }

    public function getLatestFromCache(
        string $fq_classlike_name_lc,
        ?string $file_path,
        ?string $file_contents
    ): ClassLikeStorage {
        $cached_value = $this->loadFromCache($fq_classlike_name_lc, $file_path);

        if (!$cached_value) {
            throw new \UnexpectedValueException($fq_classlike_name_lc . ' should be in cache');
        }

        $cache_hash = $this->getCacheHash($file_path, $file_contents);

        /** @psalm-suppress TypeDoesNotContainType */
        if (@get_class($cached_value) === '__PHP_Incomplete_Class'
            || $cache_hash !== $cached_value->hash
        ) {
            unlink($this->getCacheLocationForClass($fq_classlike_name_lc, $file_path));

            throw new \UnexpectedValueException($fq_classlike_name_lc . ' should not be outdated');
        }

        return $cached_value;
    }

    private function getCacheHash(?string $file_path, ?string $file_contents): string
    {
        return sha1(($file_path ? $file_contents : '') . $this->modified_timestamps);
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    private function loadFromCache(string $fq_classlike_name_lc, ?string $file_path): ?ClassLikeStorage
    {
        $cache_location = $this->getCacheLocationForClass($fq_classlike_name_lc, $file_path);

        if (file_exists($cache_location)) {
            if ($this->config->use_igbinary) {
                $storage = igbinary_unserialize((string)file_get_contents($cache_location));

                if ($storage instanceof ClassLikeStorage) {
                    return $storage;
                }

                return null;
            }

            $storage = unserialize((string)file_get_contents($cache_location));

            if ($storage instanceof ClassLikeStorage) {
                return $storage;
            }

            return null;
        }

        return null;
    }

    private function getCacheLocationForClass(
        string $fq_classlike_name_lc,
        ?string $file_path,
        bool $create_directory = false
    ): string {
        $root_cache_directory = $this->config->getCacheDirectory();

        if (!$root_cache_directory) {
            throw new \UnexpectedValueException('No cache directory defined');
        }

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::CLASS_CACHE_DIRECTORY;

        if ($create_directory && !is_dir($parser_cache_directory)) {
            mkdir($parser_cache_directory, 0777, true);
        }

        return $parser_cache_directory
            . DIRECTORY_SEPARATOR
            . sha1(($file_path ? strtolower($file_path) . ' ' : '') . $fq_classlike_name_lc)
            . ($this->config->use_igbinary ? '-igbinary' : '');
    }
}
