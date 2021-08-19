<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp;

class CompilationResult
{
    /**
     * @var string
     */
    private $css;

    /**
     * @var string|null
     */
    private $sourceMap;

    /**
     * @var string[]
     */
    private $includedFiles;

    /**
     * @param string $css
     * @param string|null $sourceMap
     * @param string[] $includedFiles
     */
    public function __construct($css, $sourceMap, array $includedFiles)
    {
        $this->css = $css;
        $this->sourceMap = $sourceMap;
        $this->includedFiles = $includedFiles;
    }

    /**
     * @return string
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @return string[]
     */
    public function getIncludedFiles()
    {
        return $this->includedFiles;
    }

    /**
     * The sourceMap content, if it was generated
     *
     * @return null|string
     */
    public function getSourceMap()
    {
        return $this->sourceMap;
    }
}
