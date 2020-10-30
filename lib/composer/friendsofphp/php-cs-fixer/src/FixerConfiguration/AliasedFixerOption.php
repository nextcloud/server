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

namespace PhpCsFixer\FixerConfiguration;

/**
 * @author ntzm
 *
 * @internal
 *
 * @todo 3.0 Drop this class
 */
final class AliasedFixerOption implements FixerOptionInterface
{
    /**
     * @var FixerOptionInterface
     */
    private $fixerOption;

    /**
     * @var string
     */
    private $alias;

    public function __construct(FixerOptionInterface $fixerOption, $alias)
    {
        $this->fixerOption = $fixerOption;
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->fixerOption->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->fixerOption->getDescription();
    }

    /**
     * {@inheritdoc}
     */
    public function hasDefault()
    {
        return $this->fixerOption->hasDefault();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return $this->fixerOption->getDefault();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedTypes()
    {
        return $this->fixerOption->getAllowedTypes();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedValues()
    {
        return $this->fixerOption->getAllowedValues();
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizer()
    {
        return $this->fixerOption->getNormalizer();
    }
}
