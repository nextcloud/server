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
 * @author Andreas Möller <am@localheinz.com>
 */
final class VersionSpecificCodeSample implements VersionSpecificCodeSampleInterface
{
    /**
     * @var CodeSampleInterface
     */
    private $codeSample;

    /**
     * @var VersionSpecificationInterface
     */
    private $versionSpecification;

    /**
     * @param string $code
     */
    public function __construct(
        $code,
        VersionSpecificationInterface $versionSpecification,
        array $configuration = null
    ) {
        $this->codeSample = new CodeSample($code, $configuration);
        $this->versionSpecification = $versionSpecification;
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
    public function isSuitableFor($version)
    {
        return $this->versionSpecification->isSatisfiedBy($version);
    }
}
