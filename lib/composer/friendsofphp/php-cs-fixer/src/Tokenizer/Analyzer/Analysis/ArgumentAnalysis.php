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
final class ArgumentAnalysis
{
    /**
     * The default value of the argument.
     *
     * @var null|string
     */
    private $default;

    /**
     * The name of the argument.
     *
     * @var string
     */
    private $name;

    /**
     * The index where the name is located in the supplied Tokens object.
     *
     * @var int
     */
    private $nameIndex;

    /**
     * The type analysis of the argument.
     *
     * @var null|TypeAnalysis
     */
    private $typeAnalysis;

    /**
     * @param string      $name
     * @param int         $nameIndex
     * @param null|string $default
     */
    public function __construct($name, $nameIndex, $default, TypeAnalysis $typeAnalysis = null)
    {
        $this->name = $name;
        $this->nameIndex = $nameIndex;
        $this->default = $default ?: null;
        $this->typeAnalysis = $typeAnalysis ?: null;
    }

    /**
     * @return null|string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return bool
     */
    public function hasDefault()
    {
        return null !== $this->default;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getNameIndex()
    {
        return $this->nameIndex;
    }

    /**
     * @return null|TypeAnalysis
     */
    public function getTypeAnalysis()
    {
        return $this->typeAnalysis;
    }

    /**
     * @return bool
     */
    public function hasTypeAnalysis()
    {
        return null !== $this->typeAnalysis;
    }
}
