<?php
namespace Psalm\Config;

use Psalm\Internal\Composer;
use function array_merge;
use function array_shift;
use function array_unique;
use function count;
use const DIRECTORY_SEPARATOR;
use function explode;
use function file_exists;
use function file_get_contents;
use function glob;
use function implode;
use function is_array;
use function is_dir;
use function json_decode;
use function preg_replace;
use Psalm\Exception\ConfigCreationException;
use function sort;
use function str_replace;
use function strpos;
use function ksort;
use function array_filter;
use function array_sum;
use function array_keys;
use function max;
use const GLOB_NOSORT;

class Creator
{
    private const TEMPLATE = '<?xml version="1.0"?>
<psalm
    errorLevel="1"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
>
    <projectFiles>
        <directory name="src" />
        <ignoreFiles>
            <directory name="vendor" />
        </ignoreFiles>
    </projectFiles>
</psalm>
';

    public static function getContents(
        string $current_dir,
        ?string $suggested_dir,
        int $level,
        string $vendor_dir
    ) : string {
        $paths = self::getPaths($current_dir, $suggested_dir);

        $template = str_replace(
            '<directory name="src" />',
            implode("\n        ", $paths),
            self::TEMPLATE
        );

        $template = str_replace(
            '<directory name="vendor" />',
            '<directory name="' . $vendor_dir . '" />',
            $template
        );

        $template = str_replace(
            'errorLevel="1"',
            'errorLevel="' . $level . '"',
            $template
        );

        return $template;
    }

    public static function createBareConfig(
        string $current_dir,
        ?string $suggested_dir,
        string $vendor_dir
    ) : void {
        $config_contents = self::getContents($current_dir, $suggested_dir, 1, $vendor_dir);

        \Psalm\Config::loadFromXML($current_dir, $config_contents);
    }

    /**
     * @param  array<\Psalm\Internal\Analyzer\IssueData>  $issues
     */
    public static function getLevel(array $issues, int $counted_types) : int
    {
        if ($counted_types === 0) {
            $counted_types = 1;
        }

        $issues_at_level = [];

        foreach ($issues as $issue) {
            $issue_type = $issue->type;
            $issue_level = $issue->error_level;

            if ($issue_level < 1) {
                continue;
            }

            // exclude some directories that are probably ignorable
            if (strpos($issue->file_path, 'vendor') || strpos($issue->file_path, 'stub')) {
                continue;
            }

            if (!isset($issues_at_level[$issue_level][$issue_type])) {
                $issues_at_level[$issue_level][$issue_type] = 0;
            }

            $issues_at_level[$issue_level][$issue_type] += 100/$counted_types;
        }

        foreach ($issues_at_level as $level => $issues) {
            ksort($issues);

            // remove any issues where < 0.1% of expressions are affected
            $filtered_issues = array_filter(
                $issues,
                function ($amount): bool {
                    return $amount > 0.1;
                }
            );

            if (array_sum($filtered_issues) > 0.5) {
                $issues_at_level[$level] = $filtered_issues;
            } else {
                unset($issues_at_level[$level]);
            }
        }

        if (!$issues_at_level) {
            return 1;
        }

        if (count($issues_at_level) === 1) {
            return array_keys($issues_at_level)[0] + 1;
        }

        return max(...array_keys($issues_at_level)) + 1;
    }

    /**
     * @return non-empty-list<string>
     */
    public static function getPaths(string $current_dir, ?string $suggested_dir): array
    {
        $replacements = [];

        if ($suggested_dir) {
            if (is_dir($current_dir . DIRECTORY_SEPARATOR . $suggested_dir)) {
                $replacements[] = '<directory name="' . $suggested_dir . '" />';
            } else {
                $bad_dir_path = $current_dir . DIRECTORY_SEPARATOR . $suggested_dir;

                throw new ConfigCreationException(
                    'The given path "' . $bad_dir_path . '" does not appear to be a directory'
                );
            }
        } elseif (is_dir($current_dir . DIRECTORY_SEPARATOR . 'src')) {
            $replacements[] = '<directory name="src" />';
        } else {
            $composer_json_location = Composer::getJsonFilePath($current_dir);

            if (!file_exists($composer_json_location)) {
                throw new ConfigCreationException(
                    'Problem during config autodiscovery - could not find composer.json during initialization.'
                );
            }

            /** @psalm-suppress MixedAssignment */
            if (!$composer_json = json_decode(file_get_contents($composer_json_location), true)) {
                throw new ConfigCreationException('Invalid composer.json at ' . $composer_json_location);
            }

            if (!is_array($composer_json)) {
                throw new ConfigCreationException('Invalid composer.json at ' . $composer_json_location);
            }

            $replacements = self::getPsr4Or0Paths($current_dir, $composer_json);

            if (!$replacements) {
                throw new ConfigCreationException(
                    'Could not located any PSR-0 or PSR-4-compatible paths in ' . $composer_json_location
                );
            }
        }

        return $replacements;
    }

    /**
     * @return list<string>
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     */
    private static function getPsr4Or0Paths(string $current_dir, array $composer_json) : array
    {
        $psr_paths = array_merge(
            $composer_json['autoload']['psr-4'] ?? [],
            $composer_json['autoload']['psr-0'] ?? []
        );

        if (!$psr_paths) {
            return self::guessPhpFileDirs($current_dir);
        }

        $nodes = [];

        foreach ($psr_paths as $paths) {
            if (!is_array($paths)) {
                $paths = [$paths];
            }

            foreach ($paths as $path) {
                if ($path === '') {
                    $nodes = array_merge(
                        $nodes,
                        self::guessPhpFileDirs($current_dir)
                    );

                    continue;
                }

                $path = preg_replace('@[\\\\/]$@', '', $path);

                if ($path !== 'tests') {
                    $nodes[] = '<directory name="' . $path . '" />';
                }
            }
        }

        $nodes = array_unique($nodes);

        sort($nodes);

        return $nodes;
    }

    /**
     * @return list<string>
     */
    private static function guessPhpFileDirs(string $current_dir) : array
    {
        $nodes = [];

        /** @var string[] */
        $php_files = array_merge(
            glob($current_dir . DIRECTORY_SEPARATOR . '*.php', GLOB_NOSORT),
            glob($current_dir . DIRECTORY_SEPARATOR . '**/*.php', GLOB_NOSORT),
            glob($current_dir . DIRECTORY_SEPARATOR . '**/**/*.php', GLOB_NOSORT)
        );

        foreach ($php_files as $php_file) {
            $php_file = str_replace($current_dir . DIRECTORY_SEPARATOR, '', $php_file);

            $parts = explode(DIRECTORY_SEPARATOR, $php_file);

            if (!$parts[0]) {
                array_shift($parts);
            }

            if ($parts[0] === 'vendor' || $parts[0] === 'tests') {
                continue;
            }

            if (count($parts) === 1) {
                $nodes[] = '<file name="' . $php_file . '" />';
            } else {
                $nodes[] = '<directory name="' . $parts[0] . '" />';
            }
        }

        return \array_values(\array_unique($nodes));
    }
}
