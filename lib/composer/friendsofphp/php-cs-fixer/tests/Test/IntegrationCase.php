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

namespace PhpCsFixer\Tests\Test;

use PhpCsFixer\RuleSet;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class IntegrationCase
{
    private $config;

    /**
     * @var string
     */
    private $expectedCode;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var null|string
     */
    private $inputCode;

    /**
     * Env requirements (possible keys: php).
     *
     * @var array
     */
    private $requirements;

    /**
     * @var RuleSet
     */
    private $ruleset;

    /**
     * Settings how to perform the test (possible keys: none in base class, use as extension point for custom IntegrationTestCase).
     *
     * @var array
     */
    private $settings;

    /**
     * @var string
     */
    private $title;

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
        $this->fileName = $fileName;
        $this->title = $title;
        $this->settings = $settings;
        $this->requirements = $requirements;
        $this->config = $config;
        $this->ruleset = $ruleset;
        $this->expectedCode = $expectedCode;
        $this->inputCode = $inputCode;
    }

    public function hasInputCode()
    {
        return null !== $this->inputCode;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getExpectedCode()
    {
        return $this->expectedCode;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getInputCode()
    {
        return $this->inputCode;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getRequirement($name)
    {
        if (!\array_key_exists($name, $this->requirements)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown requirement key "%s", expected any of "%s".',
                $name,
                implode('","', array_keys($this->requirements))
            ));
        }

        return $this->requirements[$name];
    }

    public function getRequirements()
    {
        return $this->requirements;
    }

    public function getRuleset()
    {
        return $this->ruleset;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
