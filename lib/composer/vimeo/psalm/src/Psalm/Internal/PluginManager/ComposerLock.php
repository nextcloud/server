<?php
namespace Psalm\Internal\PluginManager;

use function array_merge;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use RuntimeException;

class ComposerLock
{
    /** @var string[] */
    private $file_names;

    /** @param string[] $file_names */
    public function __construct(array $file_names)
    {
        $this->file_names = $file_names;
    }

    /**
     * @param mixed $package
     *
     * @psalm-assert-if-true array $package
     *
     * @psalm-pure
     */
    public function isPlugin($package): bool
    {
        return is_array($package)
            && isset($package['name'])
            && is_string($package['name'])
            && isset($package['type'])
            && $package['type'] === 'psalm-plugin'
            && isset($package['extra']['psalm']['pluginClass'])
            && is_array($package['extra'])
            && is_array($package['extra']['psalm'])
            && is_string($package['extra']['psalm']['pluginClass']);
    }

    /**
     * @return array<string,string> [packageName => pluginClass, ...]
     */
    public function getPlugins(): array
    {
        $pluginPackages = $this->getAllPluginPackages();
        $ret = [];
        foreach ($pluginPackages as $package) {
            $ret[$package['name']] = $package['extra']['psalm']['pluginClass'];
        }

        return $ret;
    }

    private function read(string $file_name): array
    {
        /** @psalm-suppress MixedAssignment */
        $contents = json_decode(file_get_contents($file_name), true);

        if ($error = json_last_error()) {
            throw new RuntimeException(json_last_error_msg(), $error);
        }

        if (!is_array($contents)) {
            throw new RuntimeException('Malformed ' . $file_name . ', expecting JSON-encoded object');
        }

        return $contents;
    }

    /**
     * @return list<array{type:string,name:string,extra:array{psalm:array{pluginClass:string}}}>
     */
    private function getAllPluginPackages(): array
    {
        $packages = $this->getAllPackages();
        $ret = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($packages as $package) {
            if ($this->isPlugin($package)) {
                /** @var array{type:'psalm-plugin',name:string,extra:array{psalm:array{pluginClass:string}}} */
                $ret[] = $package;
            }
        }

        return $ret;
    }

    private function getAllPackages(): array
    {
        $packages = [];
        foreach ($this->file_names as $file_name) {
            $composer_lock_contents = $this->read($file_name);
            if (!isset($composer_lock_contents['packages']) || !is_array($composer_lock_contents['packages'])) {
                throw new RuntimeException('packages section is missing or not an array');
            }
            if (!isset($composer_lock_contents['packages-dev']) || !is_array($composer_lock_contents['packages-dev'])) {
                throw new RuntimeException('packages-dev section is missing or not an array');
            }
            $packages = array_merge(
                $packages,
                array_merge(
                    $composer_lock_contents['packages'],
                    $composer_lock_contents['packages-dev']
                )
            );
        }

        return $packages;
    }
}
