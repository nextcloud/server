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

namespace PhpCsFixer\FixerDefinition;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
interface FixerDefinitionInterface
{
    /**
     * @return string
     */
    public function getSummary();

    /**
     * @return null|string
     */
    public function getDescription();

    /**
     * @return null|string null for non-configurable fixer
     *
     * @deprecated will be removed in 3.0
     */
    public function getConfigurationDescription();

    /**
     * @return null|array null for non-configurable fixer
     *
     * @deprecated will be removed in 3.0
     */
    public function getDefaultConfiguration();

    /**
     * @return null|string null for non-risky fixer
     */
    public function getRiskyDescription();

    /**
     * Array of samples, where single sample is [code, configuration].
     *
     * @return CodeSampleInterface[]
     */
    public function getCodeSamples();
}
