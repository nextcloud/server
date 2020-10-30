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

interface FixerOptionInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return bool
     */
    public function hasDefault();

    /**
     * @throws \LogicException when no default value is defined
     *
     * @return mixed
     */
    public function getDefault();

    /**
     * @return null|string[]
     */
    public function getAllowedTypes();

    /**
     * @return null|array
     */
    public function getAllowedValues();

    /**
     * @return null|\Closure
     */
    public function getNormalizer();
}
