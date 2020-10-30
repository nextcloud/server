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

namespace PhpCsFixer\Test;

use PhpCsFixer\RuleSet;
use PhpCsFixer\Tests\Test\IntegrationCase as BaseIntegrationCase;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @TODO 3.0 While removing, remove loading `tests/Test` from `autoload` section of `composer.json`.
 *
 * @deprecated since v2.4
 */
final class IntegrationCase
{
    /**
     * @var BaseIntegrationCase
     */
    private $base;

    /**
     * @param string      $fileName
     * @param string      $title
     * @param string      $expectedCode
     * @param null|string $inputCode
     */
    public function __construct(
        $fileName,
        $title,
        array $settings,
        array $requirements,
        array $config,
        RuleSet $ruleset,
        $expectedCode,
        $inputCode
    ) {
        $this->base = new BaseIntegrationCase(
            $fileName,
            $title,
            $settings,
            $requirements,
            $config,
            $ruleset,
            $expectedCode,
            $inputCode
        );
        @trigger_error(
            sprintf(
                'The "%s" class is deprecated. You should stop using it, as it will be removed in 3.0 version.',
                __CLASS__
            ),
            E_USER_DEPRECATED
        );
    }

    public function hasInputCode()
    {
        return $this->base->hasInputCode();
    }

    public function getConfig()
    {
        return $this->base->getConfig();
    }

    public function getExpectedCode()
    {
        return $this->base->getExpectedCode();
    }

    public function getFileName()
    {
        return $this->base->getFileName();
    }

    public function getInputCode()
    {
        return $this->base->getInputCode();
    }

    public function getRequirement($name)
    {
        return $this->base->getRequirement($name);
    }

    public function getRequirements()
    {
        return $this->base->getRequirements();
    }

    public function getRuleset()
    {
        return $this->base->getRuleset();
    }

    public function getSettings()
    {
        return $this->base->getSettings();
    }

    public function getTitle()
    {
        return $this->base->getTitle();
    }

    /**
     * @return bool
     *
     * @deprecated since v2.1, on ~2.1 line IntegrationTest check whether different priorities are required is done automatically, this method will be removed on v3.0
     */
    public function shouldCheckPriority()
    {
        @trigger_error(
            sprintf(
                'The "%s" method is deprecated. You should stop using it, as it will be removed in 3.0 version.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );

        $settings = $this->base->getSettings();

        return isset($settings['checkPriority']) ? $settings['checkPriority'] : true;
    }
}
