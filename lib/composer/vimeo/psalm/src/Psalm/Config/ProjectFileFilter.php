<?php
namespace Psalm\Config;

use SimpleXMLElement;
use function stripos;
use function strpos;

class ProjectFileFilter extends FileFilter
{
    /**
     * @var ProjectFileFilter|null
     */
    private $file_filter = null;

    /**
     * @return static
     */
    public static function loadFromXMLElement(
        SimpleXMLElement $e,
        string $base_dir,
        bool $inclusive
    ): ProjectFileFilter {
        $filter = parent::loadFromXMLElement($e, $base_dir, $inclusive);

        if (isset($e->ignoreFiles)) {
            if (!$inclusive) {
                throw new \Psalm\Exception\ConfigException('Cannot nest ignoreFiles inside itself');
            }

            /** @var \SimpleXMLElement $e->ignoreFiles */
            $filter->file_filter = static::loadFromXMLElement($e->ignoreFiles, $base_dir, false);
        }

        return $filter;
    }

    public function allows(string $file_name, bool $case_sensitive = false): bool
    {
        if ($this->inclusive && $this->file_filter) {
            if (!$this->file_filter->allows($file_name, $case_sensitive)) {
                return false;
            }
        }

        return parent::allows($file_name, $case_sensitive);
    }

    public function forbids(string $file_name, bool $case_sensitive = false): bool
    {
        if ($this->inclusive && $this->file_filter) {
            if (!$this->file_filter->allows($file_name, $case_sensitive)) {
                return true;
            }
        }

        return false;
    }

    public function reportTypeStats(string $file_name, bool $case_sensitive = false): bool
    {
        foreach ($this->ignore_type_stats as $exclude_dir => $_) {
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

        return true;
    }

    public function useStrictTypes(string $file_name, bool $case_sensitive = false): bool
    {
        foreach ($this->declare_strict_types as $exclude_dir => $_) {
            if ($case_sensitive) {
                if (strpos($file_name, $exclude_dir) === 0) {
                    return true;
                }
            } else {
                if (stripos($file_name, $exclude_dir) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
