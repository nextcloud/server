<?php
namespace Psalm\Internal\PluginManager;

use function array_diff_key;
use function array_flip;
use function array_key_exists;
use function array_search;
use function strpos;

class PluginList
{
    /** @var ConfigFile */
    private $config_file;

    /** @var ComposerLock */
    private $composer_lock;

    /** @var ?array<string,string> [pluginClass => packageName] */
    private $all_plugins = null;

    /** @var ?array<string,?string> [pluginClass => ?packageName] */
    private $enabled_plugins = null;

    public function __construct(ConfigFile $config_file, ComposerLock $composer_lock)
    {
        $this->config_file = $config_file;
        $this->composer_lock = $composer_lock;
    }

    /**
     * @return array<string,?string> [pluginClass => ?packageName, ...]
     */
    public function getEnabled(): array
    {
        if (!$this->enabled_plugins) {
            $this->enabled_plugins = [];
            foreach ($this->config_file->getConfig()->getPluginClasses() as $plugin_entry) {
                $plugin_class = $plugin_entry['class'];
                $this->enabled_plugins[$plugin_class] = $this->findPluginPackage($plugin_class);
            }
        }

        return $this->enabled_plugins;
    }

    /**
     * @return array<string,?string> [pluginCLass => ?packageName]
     */
    public function getAvailable(): array
    {
        return array_diff_key($this->getAll(), $this->getEnabled());
    }

    /**
     * @return array<string,string> [pluginClass => packageName]
     */
    public function getAll(): array
    {
        if (null === $this->all_plugins) {
            $this->all_plugins = array_flip($this->composer_lock->getPlugins());
        }

        return $this->all_plugins;
    }

    public function resolvePluginClass(string $class_or_package): string
    {
        if (false === strpos($class_or_package, '/')) {
            return $class_or_package; // must be a class then
        }

        // pluginClass => ?pluginPackage
        $plugin_classes = $this->getAll();

        $class = array_search($class_or_package, $plugin_classes, true);

        if (false === $class) {
            throw new \InvalidArgumentException('Unknown plugin: ' . $class_or_package);
        }

        return $class;
    }

    public function findPluginPackage(string $class): ?string
    {
        // pluginClass => ?pluginPackage
        $plugin_classes = $this->getAll();

        return $plugin_classes[$class] ?? null;
    }

    public function isEnabled(string $class): bool
    {
        return array_key_exists($class, $this->getEnabled());
    }

    public function enable(string $class): void
    {
        $this->config_file->addPlugin($class);
    }

    public function disable(string $class): void
    {
        $this->config_file->removePlugin($class);
    }
}
