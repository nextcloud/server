<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tokenizer\Analyzer\Analysis;

/**
 * @internal
 */
final class NamespaceAnalysis implements StartEndTokenAwareAnalysis
{
    /**
     * The fully qualified namespace name.
     *
     * @var string
     */
    private $fullName;

    /**
     * The short version of the namespace.
     *
     * @var string
     */
    private $shortName;

    /**
     * The start index of the namespace declaration in the analyzed Tokens.
     *
     * @var int
     */
    private $startIndex;

    /**
     * The end index of the namespace declaration in the analyzed Tokens.
     *
     * @var int
     */
    private $endIndex;

    /**
     * The start index of the scope of the namespace in the analyzed Tokens.
     *
     * @var int
     */
    private $scopeStartIndex;

    /**
     * The end index of the scope of the namespace in the analyzed Tokens.
     *
     * @var int
     */
    private $scopeEndIndex;

    /**
     * @param string $fullName
     * @param string $shortName
     * @param int    $startIndex
     * @param int    $endIndex
     * @param int    $scopeStartIndex
     * @param int    $scopeEndIndex
     */
    public function __construct($fullName, $shortName, $startIndex, $endIndex, $scopeStartIndex, $scopeEndIndex)
    {
        $this->fullName = $fullName;
        $this->shortName = $shortName;
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
        $this->scopeStartIndex = $scopeStartIndex;
        $this->scopeEndIndex = $scopeEndIndex;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * @return int
     */
    public function getStartIndex()
    {
        return $this->startIndex;
    }

    /**
     * @return int
     */
    public function getEndIndex()
    {
        return $this->endIndex;
    }

    /**
     * @return int
     */
    public function getScopeStartIndex()
    {
        return $this->scopeStartIndex;
    }

    /**
     * @return int
     */
    public function getScopeEndIndex()
    {
        return $this->scopeEndIndex;
    }
}
