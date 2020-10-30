<?php
namespace Psalm\Internal\PluginManager;

use Psalm\Internal\Composer;
use function array_filter;
use const DIRECTORY_SEPARATOR;
use function json_encode;
use function rtrim;
use function urlencode;

class PluginListFactory
{
    /** @var string */
    private $project_root;

    /** @var string */
    private $psalm_root;

    public function __construct(string $project_root, string $psalm_root)
    {
        $this->project_root = $project_root;
        $this->psalm_root = $psalm_root;
    }

    public function __invoke(string $current_dir, ?string $config_file_path = null): PluginList
    {
        $config_file = new ConfigFile($current_dir, $config_file_path);
        $composer_lock = new ComposerLock($this->findLockFiles());

        return new PluginList($config_file, $composer_lock);
    }

    /** @return non-empty-array<int,string> */
    private function findLockFiles(): array
    {
        // use cases
        // 1. plugins are installed into project vendors - composer.lock is PROJECT_ROOT/composer.lock
        // 2. plugins are installed into separate composer environment (either global or bamarni-bin)
        //  - composer.lock is PSALM_ROOT/../../../composer.lock
        // 3. plugins are installed into psalm vendors - composer.lock is PSALM_ROOT/composer.lock
        // 4. none of the above - use stub (empty virtual composer.lock)

        if ($this->psalm_root === $this->project_root) {
            // managing plugins for psalm itself
            $composer_lock_filenames = [
                Composer::getLockFilePath(rtrim($this->psalm_root, DIRECTORY_SEPARATOR)),
            ];
        } else {
            $composer_lock_filenames = [
                Composer::getLockFilePath(rtrim($this->project_root, DIRECTORY_SEPARATOR)),
                Composer::getLockFilePath(rtrim($this->psalm_root, DIRECTORY_SEPARATOR) . '/../../..'),
                Composer::getLockFilePath(rtrim($this->psalm_root, DIRECTORY_SEPARATOR)),
            ];
        }

        $composer_lock_filenames = array_filter($composer_lock_filenames, 'is_readable');

        if (empty($composer_lock_filenames)) {
            $stub_composer_lock = (object)[
                'packages' => [],
                'packages-dev' => [],
            ];
            $composer_lock_filenames[] = 'data:application/json,' . urlencode(json_encode($stub_composer_lock));
        }

        return $composer_lock_filenames;
    }
}
