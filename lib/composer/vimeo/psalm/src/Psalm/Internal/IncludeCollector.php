<?php

namespace Psalm\Internal;

use function array_diff;
use function array_merge;
use function array_unique;
use function array_values;
use function get_included_files;
use function preg_grep;

use const PREG_GREP_INVERT;

/**
 * Include collector
 *
 * Used to execute code that may cause file inclusions, and report what files have been included
 * NOTE: dependencies of this class should be kept at minimum, as it's used before autoloader is
 * registered.
 */
final class IncludeCollector
{
    /** @var list<string> */
    private $included_files = [];

    /**
     * @template T
     * @param callable():T $f
     * @return T
     */
    public function runAndCollect(callable $f)
    {
        $before = get_included_files();
        $ret = $f();
        $after = get_included_files();

        $included = array_diff($after, $before);

        $this->included_files = array_values(array_unique(array_merge($this->included_files, $included)));

        return $ret;
    }

    /** @return list<string> */
    public function getIncludedFiles(): array
    {
        return $this->included_files;
    }

    /** @return list<string> */
    public function getFilteredIncludedFiles(): array
    {
        return array_values(preg_grep('@^phar://@', $this->getIncludedFiles(), PREG_GREP_INVERT));
    }
}
