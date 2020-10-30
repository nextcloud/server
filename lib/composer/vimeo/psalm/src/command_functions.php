<?php

namespace Psalm;

use Composer\Autoload\ClassLoader;
use Phar;
use Psalm\Internal\Composer;
use function dirname;
use function strpos;
use function realpath;
use const DIRECTORY_SEPARATOR;
use function file_exists;
use function in_array;
use const PHP_EOL;
use function fwrite;
use const STDERR;
use function implode;
use function define;
use function json_decode;
use function file_get_contents;
use function is_array;
use function is_string;
use function count;
use function strlen;
use function substr;
use function stream_get_meta_data;
use const STDIN;
use function stream_set_blocking;
use function fgets;
use function preg_split;
use function trim;
use function is_dir;
use function preg_replace;
use function substr_replace;
use function file_put_contents;
use function ini_get;
use function preg_match;
use function strtoupper;

function requireAutoloaders(string $current_dir, bool $has_explicit_root, string $vendor_dir): ?ClassLoader
{
    $autoload_roots = [$current_dir];

    $psalm_dir = dirname(__DIR__);

    /** @psalm-suppress UndefinedConstant */
    $in_phar = Phar::running() || strpos(__NAMESPACE__, 'HumbugBox');

    if ($in_phar) {
        require_once(__DIR__ . '/../vendor/autoload.php');

        // hack required for JsonMapper
        require_once __DIR__ . '/../vendor/netresearch/jsonmapper/src/JsonMapper.php';
        require_once __DIR__ . '/../vendor/netresearch/jsonmapper/src/JsonMapper/Exception.php';
    }

    if (realpath($psalm_dir) !== realpath($current_dir) && !$in_phar) {
        $autoload_roots[] = $psalm_dir;
    }

    $autoload_files = [];

    foreach ($autoload_roots as $autoload_root) {
        $has_autoloader = false;

        $nested_autoload_file = dirname($autoload_root, 2). DIRECTORY_SEPARATOR . 'autoload.php';

        // note: don't realpath $nested_autoload_file, or phar version will fail
        if (file_exists($nested_autoload_file)) {
            if (!in_array($nested_autoload_file, $autoload_files, false)) {
                $autoload_files[] = $nested_autoload_file;
            }
            $has_autoloader = true;
        }

        $vendor_autoload_file =
            $autoload_root . DIRECTORY_SEPARATOR . $vendor_dir . DIRECTORY_SEPARATOR . 'autoload.php';

        // note: don't realpath $vendor_autoload_file, or phar version will fail
        if (file_exists($vendor_autoload_file)) {
            if (!in_array($vendor_autoload_file, $autoload_files, false)) {
                $autoload_files[] = $vendor_autoload_file;
            }
            $has_autoloader = true;
        }

        $composer_json_file = Composer::getJsonFilePath($autoload_root);
        if (!$has_autoloader && file_exists($composer_json_file)) {
            $error_message = 'Could not find any composer autoloaders in ' . $autoload_root;

            if (!$has_explicit_root) {
                $error_message .= PHP_EOL . 'Add a --root=[your/project/directory] flag '
                    . 'to specify a particular project to run Psalm on.';
            }

            fwrite(STDERR, $error_message . PHP_EOL);
            exit(1);
        }
    }

    $first_autoloader = null;

    foreach ($autoload_files as $file) {
        /**
         * @psalm-suppress UnresolvableInclude
         *
         * @var mixed
         */
        $autoloader = require_once $file;

        if (!$first_autoloader
            && $autoloader instanceof ClassLoader
        ) {
            $first_autoloader = $autoloader;
        }
    }

    if ($first_autoloader === null && !$in_phar) {
        if (!$autoload_files) {
            fwrite(STDERR, 'Failed to find a valid Composer autoloader' . "\n");
        } else {
            fwrite(STDERR, 'Failed to find a valid Composer autoloader in ' . implode(', ', $autoload_files) . "\n");
        }

        fwrite(
            STDERR,
            'Please make sure you’ve run `composer install` in the current directory before using Psalm.' . "\n"
        );
        exit(1);
    }

    define('PSALM_VERSION', (string)\PackageVersions\Versions::getVersion('vimeo/psalm'));
    define('PHP_PARSER_VERSION', \PackageVersions\Versions::getVersion('nikic/php-parser'));

    return $first_autoloader;
}

/**
 * @psalm-suppress MixedArrayAccess
 * @psalm-suppress MixedAssignment
 * @psalm-suppress PossiblyUndefinedStringArrayOffset
 */
function getVendorDir(string $current_dir): string
{
    $composer_json_path = Composer::getJsonFilePath($current_dir);

    if (!file_exists($composer_json_path)) {
        return 'vendor';
    }

    if (!$composer_json = json_decode(file_get_contents($composer_json_path), true)) {
        fwrite(
            STDERR,
            'Invalid composer.json at ' . $composer_json_path . "\n"
        );
        exit(1);
    }

    if (isset($composer_json['config'])
        && is_array($composer_json['config'])
        && isset($composer_json['config']['vendor-dir'])
        && is_string($composer_json['config']['vendor-dir'])
    ) {
        return $composer_json['config']['vendor-dir'];
    }

    return 'vendor';
}

/**
 * @return list<string>
 */
function getArguments() : array
{
    global $argv;

    if (!$argv) {
        return [];
    }

    $filtered_input_paths = [];

    for ($i = 0, $iMax = count($argv); $i < $iMax; ++$i) {
        $input_path = $argv[$i];

        if (realpath($input_path) !== false) {
            continue;
        }

        if ($input_path[0] === '-' && strlen($input_path) === 2) {
            if ($input_path[1] === 'c' || $input_path[1] === 'f') {
                ++$i;
            }
            continue;
        }

        if ($input_path[0] === '-' && $input_path[2] === '=') {
            continue;
        }

        $filtered_input_paths[] = $input_path;
    }

    return $filtered_input_paths;
}

/**
 * @param  string|array|null|false $f_paths
 *
 * @return list<string>|null
 */
function getPathsToCheck($f_paths): ?array
{
    global $argv;

    $paths_to_check = [];

    if ($f_paths) {
        $input_paths = is_array($f_paths) ? $f_paths : [$f_paths];
    } else {
        $input_paths = $argv ? $argv : null;
    }

    if ($input_paths) {
        $filtered_input_paths = [];

        for ($i = 0, $iMax = count($input_paths); $i < $iMax; ++$i) {
            /** @var string */
            $input_path = $input_paths[$i];

            if (realpath($input_path) === realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'psalm')
                || realpath($input_path) === realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'psalter')
                || realpath($input_path) === realpath(Phar::running(false))
            ) {
                continue;
            }

            if ($input_path[0] === '-' && strlen($input_path) === 2) {
                if ($input_path[1] === 'c' || $input_path[1] === 'f') {
                    ++$i;
                }
                continue;
            }

            if ($input_path[0] === '-' && $input_path[2] === '=') {
                continue;
            }

            if (substr($input_path, 0, 2) === '--' && strlen($input_path) > 2) {
                continue;
            }

            $filtered_input_paths[] = $input_path;
        }

        if ($filtered_input_paths === ['-']) {
            $meta = stream_get_meta_data(STDIN);
            stream_set_blocking(STDIN, false);
            if ($stdin = fgets(STDIN)) {
                $filtered_input_paths = preg_split('/\s+/', trim($stdin));
            }
            $blocked = $meta['blocked'];
            stream_set_blocking(STDIN, $blocked);
        }

        foreach ($filtered_input_paths as $path_to_check) {
            if ($path_to_check[0] === '-') {
                fwrite(STDERR, 'Invalid usage, expecting psalm [options] [file...]' . PHP_EOL);
                exit(1);
            }

            if (!file_exists($path_to_check)) {
                fwrite(STDERR, 'Cannot locate ' . $path_to_check . PHP_EOL);
                exit(1);
            }

            $path_to_check = realpath($path_to_check);

            if (!$path_to_check) {
                fwrite(STDERR, 'Error getting realpath for file' . PHP_EOL);
                exit(1);
            }

            $paths_to_check[] = $path_to_check;
        }

        if (!$paths_to_check) {
            $paths_to_check = null;
        }
    }

    return $paths_to_check;
}

/**
 * @psalm-pure
 */
function getPsalmHelpText(): string
{
    return <<<HELP
Usage:
    psalm [options] [file...]

Basic configuration:
    -c, --config=psalm.xml
        Path to a psalm.xml configuration file. Run psalm --init to create one.

    --use-ini-defaults
        Use PHP-provided ini defaults for memory and error display

    --memory-limit=LIMIT
        Use a specific memory limit. Cannot be combined with --use-ini-defaults

    --disable-extension=[extension]
        Used to disable certain extensions while Psalm is running.

    --threads=INT
        If greater than one, Psalm will run analysis on multiple threads, speeding things up.

    --no-diff
        Turns off Psalm’s diff mode, checks all files regardless of whether they've changed

    --diff-methods
        Only checks methods that have changed since last run (and their dependents)

Surfacing issues:
    --show-info[=BOOLEAN]
        Show non-exception parser findings (defaults to false).

    --show-snippet[=true]
        Show code snippets with errors. Options are 'true' or 'false'

    --find-dead-code[=auto]
    --find-unused-code[=auto]
        Look for unused code. Options are 'auto' or 'always'. If no value is specified, default is 'auto'

    --find-unused-psalm-suppress
        Finds all @psalm-suppress annotations that aren’t used

    --find-references-to=[class|method|property]
        Searches the codebase for references to the given fully-qualified class or method,
        where method is in the format class::methodName

    --no-suggestions
        Hide suggestions

    --taint-analysis
        Run Psalm in taint analysis mode – see https://psalm.dev/docs/security_analysis for more info

Issue baselines:
    --set-baseline=PATH
        Save all current error level issues to a file, to mark them as info in subsequent runs

        Add --include-php-versions to also include a list of PHP extension versions

    --use-baseline=PATH
        Allows you to use a baseline other than the default baseline provided in your config

    --ignore-baseline
        Ignore the error baseline

    --update-baseline
        Update the baseline by removing fixed issues. This will not add new issues to the baseline

        Add --include-php-versions to also include a list of PHP extension versions

Plugins:
    --plugin=PATH
        Executes a plugin, an alternative to using the Psalm config

Output:
    -m, --monochrome
        Enable monochrome output

    --output-format=console
        Changes the output format.
        Available formats: compact, console, text, emacs, json, pylint, xml, checkstyle, junit, sonarqube, github,
                           phpstorm

    --no-progress
        Disable the progress indicator

    --long-progress
        Use a progress indicator suitable for Continuous Integration logs

    --stats
        Shows a breakdown of Psalm's ability to infer types in the codebase

Reports:
    --report=PATH
        The path where to output report file. The output format is based on the file extension.
        (Currently supported formats: ".json", ".xml", ".txt", ".emacs", ".pylint", ".console",
        "checkstyle.xml", "sonarqube.json", "summary.json", "junit.xml")

    --report-show-info[=BOOLEAN]
        Whether the report should include non-errors in its output (defaults to true)

Caching:
    --clear-cache
        Clears all cache files that Psalm uses for this specific project

    --clear-global-cache
        Clears all cache files that Psalm uses for all projects

    --no-cache
        Runs Psalm without using cache

    --no-reflection-cache
        Runs Psalm without using cached representations of unchanged classes and files.
        Useful if you want the afterClassLikeVisit plugin hook to run every time you visit a file.

    --no-file-cache
        Runs Psalm without using caching every single file for later diffing.
        This reduces the space Psalm uses on disk and file I/O.

Miscellaneous:
    -h, --help
        Display this help message

    -v, --version
        Display the Psalm version

    -i, --init [source_dir=src] [level=3]
        Create a psalm config file in the current directory that points to [source_dir]
        at the required level, from 1, most strict, to 8, most permissive.

    --debug
        Debug information

    --debug-by-line
        Debug information on a line-by-line level

    --debug-emitted-issues
        Print a php backtrace to stderr when emitting issues.

    -r, --root
        If running Psalm globally you'll need to specify a project root. Defaults to cwd

    --generate-json-map=PATH
        Generate a map of node references and types in JSON format, saved to the given path.

    --generate-stubs=PATH
        Generate stubs for the project and dump the file in the given path

    --shepherd[=host]
        Send data to Shepherd, Psalm's GitHub integration tool.

    --alter
        Run Psalter

    --language-server
        Run Psalm Language Server

HELP;
}

function initialiseConfig(
    ?string $path_to_config,
    string $current_dir,
    string $output_format,
    ?ClassLoader $first_autoloader
): Config {
    try {
        if ($path_to_config) {
            $config = Config::loadFromXMLFile($path_to_config, $current_dir);
        } else {
            $config = Config::getConfigForPath($current_dir, $current_dir, $output_format);
        }
    } catch (\Psalm\Exception\ConfigException $e) {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }

    $config->setComposerClassLoader($first_autoloader);

    return $config;
}

function update_config_file(Config $config, string $config_file_path, string $baseline_path) : void
{
    if ($config->error_baseline === $baseline_path) {
        return;
    }

    $configFile = $config_file_path;

    if (is_dir($config_file_path)) {
        $configFile = Config::locateConfigFile($config_file_path);
    }

    if (!$configFile) {
        fwrite(STDERR, "Don't forget to set errorBaseline=\"{$baseline_path}\" to your config.");

        return;
    }

    $configFileContents = file_get_contents($configFile);

    if ($config->error_baseline) {
        $amendedConfigFileContents = preg_replace(
            '/errorBaseline=".*?"/',
            "errorBaseline=\"{$baseline_path}\"",
            $configFileContents
        );
    } else {
        $endPsalmOpenTag = strpos($configFileContents, '>', (int)strpos($configFileContents, '<psalm'));

        if (!$endPsalmOpenTag) {
            fwrite(STDERR, " Don't forget to set errorBaseline=\"{$baseline_path}\" in your config.");
            return;
        }

        if ($configFileContents[$endPsalmOpenTag - 1] === "\n") {
            $amendedConfigFileContents = substr_replace(
                $configFileContents,
                "    errorBaseline=\"{$baseline_path}\"\n>",
                $endPsalmOpenTag,
                1
            );
        } else {
            $amendedConfigFileContents = substr_replace(
                $configFileContents,
                " errorBaseline=\"{$baseline_path}\">",
                $endPsalmOpenTag,
                1
            );
        }
    }

    file_put_contents($configFile, $amendedConfigFileContents);
}

function get_path_to_config(array $options): ?string
{
    $path_to_config = isset($options['c']) && is_string($options['c']) ? realpath($options['c']) : null;

    if ($path_to_config === false) {
        fwrite(STDERR, 'Could not resolve path to config ' . (string) ($options['c'] ?? '') . PHP_EOL);
        exit(1);
    }
    return $path_to_config;
}

/**
 * @psalm-pure
 */
function getMemoryLimitInBytes(): int
{
    $limit = ini_get('memory_limit');
    // for unlimited = -1
    if ($limit < 0) {
        return -1;
    }

    if (preg_match('/^(\d+)(\D?)$/', $limit, $matches)) {
        $limit = (int)$matches[1];
        switch (strtoupper($matches[2] ?? '')) {
            case 'G':
                $limit *= 1024 * 1024 * 1024;
                break;
            case 'M':
                $limit *= 1024 * 1024;
                break;
            case 'K':
                $limit *= 1024;
                break;
        }
    }

    return (int)$limit;
}
