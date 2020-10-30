<?php
namespace Psalm\Config;

use function array_filter;
use function array_map;
use const DIRECTORY_SEPARATOR;
use const E_WARNING;
use function explode;
use function glob;
use function in_array;
use function is_dir;
use function preg_match;
use function preg_replace;
use Psalm\Exception\ConfigException;
use function readlink;
use function realpath;
use function restore_error_handler;
use function set_error_handler;
use SimpleXMLElement;
use function str_replace;
use function stripos;
use function strpos;
use function strtolower;
use const GLOB_NOSORT;
use const GLOB_ONLYDIR;

/**
 * @psalm-consistent-constructor
 */
class FileFilter
{
    /**
     * @var array<string>
     */
    protected $directories = [];

    /**
     * @var array<string>
     */
    protected $files = [];

    /**
     * @var array<string>
     */
    protected $fq_classlike_names = [];

    /**
     * @var array<string>
     */
    protected $fq_classlike_patterns = [];

    /**
     * @var array<string>
     */
    protected $method_ids = [];

    /**
     * @var array<string>
     */
    protected $property_ids = [];

    /**
     * @var array<string>
     */
    protected $var_names = [];

    /**
     * @var array<string>
     */
    protected $files_lowercase = [];

    /**
     * @var bool
     */
    protected $inclusive;

    /**
     * @var array<string, bool>
     */
    protected $ignore_type_stats = [];

    /**
     * @var array<string, bool>
     */
    protected $declare_strict_types = [];

    public function __construct(bool $inclusive)
    {
        $this->inclusive = $inclusive;
    }

    /**
     * @return static
     */
    public static function loadFromXMLElement(
        SimpleXMLElement $e,
        string $base_dir,
        bool $inclusive
    ) {
        $allow_missing_files = ((string) $e['allowMissingFiles']) === 'true';

        $filter = new static($inclusive);

        if ($e->directory) {
            /** @var \SimpleXMLElement $directory */
            foreach ($e->directory as $directory) {
                $directory_path = (string) $directory['name'];
                $ignore_type_stats = strtolower(
                    isset($directory['ignoreTypeStats']) ? (string) $directory['ignoreTypeStats'] : ''
                ) === 'true';
                $declare_strict_types = strtolower(
                    isset($directory['useStrictTypes']) ? (string) $directory['useStrictTypes'] : ''
                ) === 'true';

                if ($directory_path[0] === '/' && DIRECTORY_SEPARATOR === '/') {
                    $prospective_directory_path = $directory_path;
                } else {
                    $prospective_directory_path = $base_dir . DIRECTORY_SEPARATOR . $directory_path;
                }

                if (strpos($prospective_directory_path, '*') !== false) {
                    $globs = array_map(
                        'realpath',
                        glob($prospective_directory_path, GLOB_ONLYDIR)
                    );

                    if (empty($globs)) {
                        if ($allow_missing_files) {
                            continue;
                        }

                        throw new ConfigException(
                            'Could not resolve config path to ' . $base_dir
                                . DIRECTORY_SEPARATOR . (string)$directory['name']
                        );
                    }

                    foreach ($globs as $glob_index => $directory_path) {
                        if (!$directory_path) {
                            if ($allow_missing_files) {
                                continue;
                            }

                            throw new ConfigException(
                                'Could not resolve config path to ' . $base_dir
                                    . DIRECTORY_SEPARATOR . (string)$directory['name'] . ':' . $glob_index
                            );
                        }

                        if ($ignore_type_stats && $filter instanceof ProjectFileFilter) {
                            $filter->ignore_type_stats[$directory_path] = true;
                        }

                        if ($declare_strict_types && $filter instanceof ProjectFileFilter) {
                            $filter->declare_strict_types[$directory_path] = true;
                        }

                        $filter->addDirectory($directory_path);
                    }
                    continue;
                }

                $directory_path = realpath($prospective_directory_path);

                if (!$directory_path) {
                    if ($allow_missing_files) {
                        continue;
                    }

                    throw new ConfigException(
                        'Could not resolve config path to ' . $base_dir
                            . DIRECTORY_SEPARATOR . (string)$directory['name']
                    );
                }

                if (!is_dir($directory_path)) {
                    throw new ConfigException(
                        $base_dir . DIRECTORY_SEPARATOR . (string)$directory['name']
                            . ' is not a directory'
                    );
                }

                /** @var \RecursiveDirectoryIterator */
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory_path));
                $iterator->rewind();

                while ($iterator->valid()) {
                    if (!$iterator->isDot() && $iterator->isLink()) {
                        $linked_path = readlink($iterator->getPathname());

                        if (stripos($linked_path, $directory_path) !== 0) {
                            if ($ignore_type_stats && $filter instanceof ProjectFileFilter) {
                                $filter->ignore_type_stats[$directory_path] = true;
                            }

                            if ($declare_strict_types && $filter instanceof ProjectFileFilter) {
                                $filter->declare_strict_types[$directory_path] = true;
                            }

                            if (is_dir($linked_path)) {
                                $filter->addDirectory($linked_path);
                            }
                        }
                    }

                    $iterator->next();
                }

                if ($ignore_type_stats && $filter instanceof ProjectFileFilter) {
                    $filter->ignore_type_stats[$directory_path] = true;
                }

                if ($declare_strict_types && $filter instanceof ProjectFileFilter) {
                    $filter->declare_strict_types[$directory_path] = true;
                }

                $filter->addDirectory($directory_path);
            }
        }

        if ($e->file) {
            /** @var \SimpleXMLElement $file */
            foreach ($e->file as $file) {
                $file_path = (string) $file['name'];

                if ($file_path[0] === '/' && DIRECTORY_SEPARATOR === '/') {
                    $prospective_file_path = $file_path;
                } else {
                    $prospective_file_path = $base_dir . DIRECTORY_SEPARATOR . $file_path;
                }

                if (strpos($prospective_file_path, '*') !== false) {
                    $globs = array_map(
                        'realpath',
                        array_filter(
                            glob($prospective_file_path, GLOB_NOSORT),
                            'file_exists'
                        )
                    );

                    if (empty($globs)) {
                        throw new ConfigException(
                            'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                                (string)$file['name']
                        );
                    }

                    foreach ($globs as $glob_index => $file_path) {
                        if (!$file_path) {
                            throw new ConfigException(
                                'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                                    (string)$file['name'] . ':' . $glob_index
                            );
                        }
                        $filter->addFile($file_path);
                    }
                    continue;
                }

                $file_path = realpath($prospective_file_path);

                if (!$file_path && !$allow_missing_files) {
                    throw new ConfigException(
                        'Could not resolve config path to ' . $base_dir . DIRECTORY_SEPARATOR .
                        (string)$file['name']
                    );
                }

                $filter->addFile($file_path);
            }
        }

        if ($e->referencedClass) {
            /** @var \SimpleXMLElement $referenced_class */
            foreach ($e->referencedClass as $referenced_class) {
                $class_name = strtolower((string)$referenced_class['name']);

                if (strpos($class_name, '*') !== false) {
                    $regex = '/' . \str_replace('*', '.*', str_replace('\\', '\\\\', $class_name)) . '/i';
                    $filter->fq_classlike_patterns[] = $regex;
                } else {
                    $filter->fq_classlike_names[] = $class_name;
                }
            }
        }

        if ($e->referencedMethod) {
            /** @var \SimpleXMLElement $referenced_method */
            foreach ($e->referencedMethod as $referenced_method) {
                $method_id = (string)$referenced_method['name'];

                if (!preg_match('/^[^:]+::[^:]+$/', $method_id) && !static::isRegularExpression($method_id)) {
                    throw new ConfigException(
                        'Invalid referencedMethod ' . $method_id
                    );
                }

                $filter->method_ids[] = strtolower($method_id);
            }
        }

        if ($e->referencedFunction) {
            /** @var \SimpleXMLElement $referenced_function */
            foreach ($e->referencedFunction as $referenced_function) {
                $filter->method_ids[] = strtolower((string)$referenced_function['name']);
            }
        }

        if ($e->referencedProperty) {
            /** @var \SimpleXMLElement $referenced_property */
            foreach ($e->referencedProperty as $referenced_property) {
                $filter->property_ids[] = strtolower((string)$referenced_property['name']);
            }
        }

        if ($e->referencedVariable) {
            /** @var \SimpleXMLElement $referenced_variable */
            foreach ($e->referencedVariable as $referenced_variable) {
                $filter->var_names[] = strtolower((string)$referenced_variable['name']);
            }
        }

        return $filter;
    }

    private static function isRegularExpression(string $string) : bool
    {
        set_error_handler(
            function () : bool {
                return false;
            },
            E_WARNING
        );
        $is_regexp = preg_match($string, '') !== false;
        restore_error_handler();

        return $is_regexp;
    }

    /**
     * @psalm-pure
     */
    protected static function slashify(string $str): string
    {
        return preg_replace('/\/?$/', DIRECTORY_SEPARATOR, $str);
    }

    public function allows(string $file_name, bool $case_sensitive = false): bool
    {
        if ($this->inclusive) {
            foreach ($this->directories as $include_dir) {
                if ($case_sensitive) {
                    if (strpos($file_name, $include_dir) === 0) {
                        return true;
                    }
                } else {
                    if (stripos($file_name, $include_dir) === 0) {
                        return true;
                    }
                }
            }

            if ($case_sensitive) {
                if (in_array($file_name, $this->files, true)) {
                    return true;
                }
            } else {
                if (in_array(strtolower($file_name), $this->files_lowercase, true)) {
                    return true;
                }
            }

            return false;
        }

        // exclusive
        foreach ($this->directories as $exclude_dir) {
            if ($case_sensitive) {
                if (strpos($file_name, $exclude_dir) === 0) {
                    return false;
                }
            } else {
                if (stripos($file_name, $exclude_dir) === 0) {
                    return false;
                }
            }
        }

        if ($case_sensitive) {
            if (in_array($file_name, $this->files, true)) {
                return false;
            }
        } else {
            if (in_array(strtolower($file_name), $this->files_lowercase, true)) {
                return false;
            }
        }

        return true;
    }

    public function allowsClass(string $fq_classlike_name): bool
    {
        if ($this->fq_classlike_patterns) {
            foreach ($this->fq_classlike_patterns as $pattern) {
                if (preg_match($pattern, $fq_classlike_name)) {
                    return true;
                }
            }
        }

        return in_array(strtolower($fq_classlike_name), $this->fq_classlike_names, true);
    }

    public function allowsMethod(string $method_id): bool
    {
        if (!$this->method_ids) {
            return false;
        }

        if (preg_match('/^[^:]+::[^:]+$/', $method_id)) {
            $method_stub = '*::' . explode('::', $method_id)[1];

            foreach ($this->method_ids as $config_method_id) {
                if ($config_method_id === $method_id) {
                    return true;
                }

                if ($config_method_id === $method_stub) {
                    return true;
                }

                if ($config_method_id[0] === '/' && preg_match($config_method_id, $method_id)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($method_id, $this->method_ids, true);
    }

    public function allowsProperty(string $property_id): bool
    {
        return in_array(strtolower($property_id), $this->property_ids, true);
    }

    public function allowsVariable(string $var_name): bool
    {
        return in_array(strtolower($var_name), $this->var_names, true);
    }

    /**
     * @return array<string>
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * @return array<string>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function addFile(string $file_name): void
    {
        $this->files[] = $file_name;
        $this->files_lowercase[] = strtolower($file_name);
    }

    public function addDirectory(string $dir_name): void
    {
        $this->directories[] = self::slashify($dir_name);
    }
}
