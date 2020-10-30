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
 *
 * @internal
 */
final class FileSpecificCodeSample implements FileSpecificCodeSampleInterface
{
    /**
     * @var CodeSampleInterface
     */
    private $codeSample;

    /**
     * @var \SplFileInfo
     */
    private $splFileInfo;

    /**
     * @param string $code
     */
    public function __construct(
        $code,
        \SplFileInfo $splFileInfo,
        array $configuration = null
    ) {
        $this->codeSample = new CodeSample($code, $configuration);
        $this->splFileInfo = $splFileInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return $this->codeSample->getCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->codeSample->getConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function getSplFileInfo()
    {
        return $this->splFileInfo;
    }
}
