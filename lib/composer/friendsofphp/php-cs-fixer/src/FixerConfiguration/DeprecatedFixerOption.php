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

final class DeprecatedFixerOption implements DeprecatedFixerOptionInterface
{
    /**
     * @var FixerOptionInterface
     */
    private $option;

    /**
     * @var string
     */
    private $deprecationMessage;

    /**
     * @param string $deprecationMessage
     */
    public function __construct(FixerOptionInterface $option, $deprecationMessage)
    {
        $this->option = $option;
        $this->deprecationMessage = $deprecationMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->option->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->option->getDescription();
    }

    /**
     * {@inheritdoc}
     */
    public function hasDefault()
    {
        return $this->option->hasDefault();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return $this->option->getDefault();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedTypes()
    {
        return $this->option->getAllowedTypes();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedValues()
    {
        return $this->option->getAllowedValues();
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizer()
    {
        return $this->option->getNormalizer();
    }

    /**
     * @return string
     */
    public function getDeprecationMessage()
    {
        return $this->deprecationMessage;
    }
}
