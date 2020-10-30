<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Finder\Iterator;

/**
 * ExcludeDirectoryFilterIterator filters out directories.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExcludeDirectoryFilterIterator extends \FilterIterator implements \RecursiveIterator
{
    private $iterator;
    private $isRecursive;
    private $excludedDirs = [];
    private $excludedPattern;

    /**
     * @param \Iterator $iterator    The Iterator to filter
     * @param string[]  $directories An array of directories to exclude
     */
    public function __construct(\Iterator $iterator, array $directories)
    {
        $this->iterator = $iterator;
        $this->isRecursive = $iterator instanceof \RecursiveIterator;
        $patterns = [];
        foreach ($directories as $directory) {
            $directory = rtrim($directory, '/');
            if (!$this->isRecursive || false !== strpos($directory, '/')) {
                $patterns[] = preg_quote($directory, '#');
            } else {
                $this->excludedDirs[$directory] = true;
            }
        }
        if ($patterns) {
            $this->excludedPattern = '#(?:^|/)(?:'.implode('|', $patterns).')(?:/|$)#';
        }

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool True if the value should be kept, false otherwise
     */
    public function accept()
    {
        if ($this->isRecursive && isset($this->excludedDirs[$this->getFilename()]) && $this->isDir()) {
            return false;
        }

        if ($this->excludedPattern) {
            $path = $this->isDir() ? $this->current()->getRelativePathname() : $this->current()->getRelativePath();
            $path = str_replace('\\', '/', $path);

            return !preg_match($this->excludedPattern, $path);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return $this->isRecursive && $this->iterator->hasChildren();
    }

    public function getChildren()
    {
        $children = new self($this->iterator->getChildren(), []);
        $children->excludedDirs = $this->excludedDirs;
        $children->excludedPattern = $this->excludedPattern;

        return $children;
    }
}
