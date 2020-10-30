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

final class FixerOptionBuilder
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var mixed
     */
    private $default;

    /**
     * @var bool
     */
    private $isRequired = true;

    /**
     * @var null|string[]
     */
    private $allowedTypes;

    /**
     * @var null|array
     */
    private $allowedValues;

    /**
     * @var null|\Closure
     */
    private $normalizer;

    /**
     * @var null|string
     */
    private $deprecationMessage;

    /**
     * @param string $name
     * @param string $description
     */
    public function __construct($name, $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * @param mixed $default
     *
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;
        $this->isRequired = false;

        return $this;
    }

    /**
     * @param string[] $allowedTypes
     *
     * @return $this
     */
    public function setAllowedTypes(array $allowedTypes)
    {
        $this->allowedTypes = $allowedTypes;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAllowedValues(array $allowedValues)
    {
        $this->allowedValues = $allowedValues;

        return $this;
    }

    /**
     * @return $this
     */
    public function setNormalizer(\Closure $normalizer)
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    /**
     * @param null|string $deprecationMessage
     *
     * @return $this
     */
    public function setDeprecationMessage($deprecationMessage)
    {
        $this->deprecationMessage = $deprecationMessage;

        return $this;
    }

    /**
     * @return FixerOptionInterface
     */
    public function getOption()
    {
        $option = new FixerOption(
            $this->name,
            $this->description,
            $this->isRequired,
            $this->default,
            $this->allowedTypes,
            $this->allowedValues,
            $this->normalizer
        );

        if (null !== $this->deprecationMessage) {
            $option = new DeprecatedFixerOption($option, $this->deprecationMessage);
        }

        return $option;
    }
}
