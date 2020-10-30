<?php
namespace Psalm\Internal\Provider;

use const DIRECTORY_SEPARATOR;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_readable;
use Psalm\Config;
use function serialize;
use function unserialize;

/**
 * @psalm-import-type  FileMapType from \Psalm\Internal\Codebase\Analyzer
 *
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 */
class FileReferenceCacheProvider
{
    private const REFERENCE_CACHE_NAME = 'references';
    private const CLASSLIKE_FILE_CACHE_NAME = 'classlike_files';
    private const NONMETHOD_CLASS_REFERENCE_CACHE_NAME = 'file_class_references';
    private const METHOD_CLASS_REFERENCE_CACHE_NAME = 'method_class_references';
    private const ANALYZED_METHODS_CACHE_NAME = 'analyzed_methods';
    private const CLASS_METHOD_CACHE_NAME = 'class_method_references';
    private const FILE_CLASS_MEMBER_CACHE_NAME = 'file_class_member_references';
    private const ISSUES_CACHE_NAME = 'issues';
    private const FILE_MAPS_CACHE_NAME = 'file_maps';
    private const TYPE_COVERAGE_CACHE_NAME = 'type_coverage';
    private const CONFIG_HASH_CACHE_NAME = 'config';
    private const METHOD_MISSING_MEMBER_CACHE_NAME = 'method_missing_member';
    private const FILE_MISSING_MEMBER_CACHE_NAME = 'file_missing_member';
    private const UNKNOWN_MEMBER_CACHE_NAME = 'unknown_member_references';
    private const METHOD_PARAM_USE_CACHE_NAME = 'method_param_uses';

    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function hasConfigChanged() : bool
    {
        $has_changed = $this->config->hash !== $this->getConfigHashCache();
        $this->setConfigHashCache($this->config->hash);
        return $has_changed;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedFileReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

        if (!is_readable($reference_cache_location)) {
            return null;
        }

        $reference_cache = unserialize((string) file_get_contents($reference_cache_location));

        if (!is_array($reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedClassLikeFiles(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASSLIKE_FILE_CACHE_NAME;

        if (!is_readable($reference_cache_location)) {
            return null;
        }

        $reference_cache = unserialize((string) file_get_contents($reference_cache_location));

        if (!is_array($reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedNonMethodClassReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::NONMETHOD_CLASS_REFERENCE_CACHE_NAME;

        if (!is_readable($reference_cache_location)) {
            return null;
        }

        $reference_cache = unserialize((string) file_get_contents($reference_cache_location));

        if (!is_array($reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMethodClassReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_CLASS_REFERENCE_CACHE_NAME;

        if (!is_readable($cache_location)) {
            return null;
        }

        $reference_cache = unserialize((string) file_get_contents($cache_location));

        if (!is_array($reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMethodMemberReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $class_member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_CACHE_NAME;

        if (!is_readable($class_member_cache_location)) {
            return null;
        }

        $class_member_reference_cache = unserialize((string) file_get_contents($class_member_cache_location));

        if (!is_array($class_member_reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMethodMissingMemberReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $class_member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_MISSING_MEMBER_CACHE_NAME;

        if (!is_readable($class_member_cache_location)) {
            return null;
        }

        $class_member_reference_cache = unserialize((string) file_get_contents($class_member_cache_location));

        if (!is_array($class_member_reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedFileMemberReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $file_class_member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_CLASS_MEMBER_CACHE_NAME;

        if (!is_readable($file_class_member_cache_location)) {
            return null;
        }

        $file_class_member_reference_cache = unserialize((string) file_get_contents($file_class_member_cache_location));

        if (!is_array($file_class_member_reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $file_class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedFileMissingMemberReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $file_class_member_cache_location
            = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_MISSING_MEMBER_CACHE_NAME;

        if (!is_readable($file_class_member_cache_location)) {
            return null;
        }

        $file_class_member_reference_cache = unserialize((string) file_get_contents($file_class_member_cache_location));

        if (!is_array($file_class_member_reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $file_class_member_reference_cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMixedMemberNameReferences(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::UNKNOWN_MEMBER_CACHE_NAME;

        if (!is_readable($cache_location)) {
            return null;
        }

        $cache = unserialize((string) file_get_contents($cache_location));

        if (!is_array($cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedMethodParamUses(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_PARAM_USE_CACHE_NAME;

        if (!is_readable($cache_location)) {
            return null;
        }

        $cache = unserialize((string) file_get_contents($cache_location));

        if (!is_array($cache)) {
            throw new \UnexpectedValueException('The method param use cache must be an array');
        }

        return $cache;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getCachedIssues(): ?array
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $issues_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ISSUES_CACHE_NAME;

        if (!is_readable($issues_cache_location)) {
            return null;
        }

        $issues_cache = unserialize((string) file_get_contents($issues_cache_location));

        if (!is_array($issues_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $issues_cache;
    }

    public function setCachedFileReferences(array $file_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

        file_put_contents($reference_cache_location, serialize($file_references));
    }

    public function setCachedClassLikeFiles(array $file_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASSLIKE_FILE_CACHE_NAME;

        file_put_contents($reference_cache_location, serialize($file_references));
    }

    public function setCachedNonMethodClassReferences(array $file_class_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::NONMETHOD_CLASS_REFERENCE_CACHE_NAME;

        file_put_contents($reference_cache_location, serialize($file_class_references));
    }

    public function setCachedMethodClassReferences(array $method_class_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_CLASS_REFERENCE_CACHE_NAME;

        file_put_contents($reference_cache_location, serialize($method_class_references));
    }

    public function setCachedMethodMemberReferences(array $member_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_CACHE_NAME;

        file_put_contents($member_cache_location, serialize($member_references));
    }

    public function setCachedMethodMissingMemberReferences(array $member_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_MISSING_MEMBER_CACHE_NAME;

        file_put_contents($member_cache_location, serialize($member_references));
    }

    public function setCachedFileMemberReferences(array $member_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_CLASS_MEMBER_CACHE_NAME;

        file_put_contents($member_cache_location, serialize($member_references));
    }

    public function setCachedFileMissingMemberReferences(array $member_references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_MISSING_MEMBER_CACHE_NAME;

        file_put_contents($member_cache_location, serialize($member_references));
    }

    public function setCachedMixedMemberNameReferences(array $references): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::UNKNOWN_MEMBER_CACHE_NAME;

        file_put_contents($cache_location, serialize($references));
    }

    public function setCachedMethodParamUses(array $uses): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::METHOD_PARAM_USE_CACHE_NAME;

        file_put_contents($cache_location, serialize($uses));
    }

    public function setCachedIssues(array $issues): void
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $issues_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ISSUES_CACHE_NAME;

        file_put_contents($issues_cache_location, serialize($issues));
    }

    /**
     * @return array<string, array<string, int>>|false
     */
    public function getAnalyzedMethodCache()
    {
        $cache_directory = $this->config->getCacheDirectory();

        $analyzed_methods_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ANALYZED_METHODS_CACHE_NAME;

        if ($cache_directory
            && file_exists($analyzed_methods_cache_location)
        ) {
            /** @var array<string, array<string, int>> */
            return unserialize(file_get_contents($analyzed_methods_cache_location));
        }

        return false;
    }

    /**
     * @param array<string, array<string, int>> $analyzed_methods
     */
    public function setAnalyzedMethodCache(array $analyzed_methods): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $analyzed_methods_cache_location = $cache_directory
                . DIRECTORY_SEPARATOR
                . self::ANALYZED_METHODS_CACHE_NAME;

            file_put_contents(
                $analyzed_methods_cache_location,
                serialize($analyzed_methods)
            );
        }
    }

    /**
     * @return array<string, FileMapType>|false
     */
    public function getFileMapCache()
    {
        $cache_directory = $this->config->getCacheDirectory();

        $file_maps_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_MAPS_CACHE_NAME;

        if ($cache_directory
            && file_exists($file_maps_cache_location)
        ) {
            /**
             * @var array<string, FileMapType>
             */
            $file_maps_cache = unserialize(file_get_contents($file_maps_cache_location));

            return $file_maps_cache;
        }

        return false;
    }

    /**
     * @param array<string, FileMapType> $file_maps
     */
    public function setFileMapCache(array $file_maps): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $file_maps_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_MAPS_CACHE_NAME;

            file_put_contents(
                $file_maps_cache_location,
                serialize($file_maps)
            );
        }
    }

    /**
     * @return array<string, array{int, int}>|false
     */
    public function getTypeCoverage()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        $type_coverage_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::TYPE_COVERAGE_CACHE_NAME;

        if ($cache_directory
            && file_exists($type_coverage_cache_location)
        ) {
            /** @var array<string, array{int, int}> */
            $type_coverage_cache = unserialize(file_get_contents($type_coverage_cache_location));

            return $type_coverage_cache;
        }

        return false;
    }

    /**
     * @param array<string, array{int, int}> $mixed_counts
     */
    public function setTypeCoverage(array $mixed_counts): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $type_coverage_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::TYPE_COVERAGE_CACHE_NAME;

            file_put_contents(
                $type_coverage_cache_location,
                serialize($mixed_counts)
            );
        }
    }

    /**
     * @return string|false
     */
    public function getConfigHashCache()
    {
        $cache_directory = $this->config->getCacheDirectory();

        $config_hash_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CONFIG_HASH_CACHE_NAME;

        if ($cache_directory
            && file_exists($config_hash_cache_location)
        ) {
            /** @var string */
            $file_maps_cache = file_get_contents($config_hash_cache_location);

            return $file_maps_cache;
        }

        return false;
    }

    public function setConfigHashCache(string $hash): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            if (!file_exists($cache_directory)) {
                \mkdir($cache_directory, 0777, true);
            }

            $config_hash_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CONFIG_HASH_CACHE_NAME;

            file_put_contents(
                $config_hash_cache_location,
                $hash
            );
        }
    }
}
