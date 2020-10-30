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
use Symfony\Component\Finder\SplFileInfo;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
abstract class AbstractIntegrationCaseFactory implements IntegrationCaseFactoryInterface
{
    /**
     * @return IntegrationCase
     */
    public function create(SplFileInfo $file)
    {
        try {
            if (!preg_match(
                '/^
                            --TEST--           \r?\n(?<title>          .*?)
                       \s   --RULESET--        \r?\n(?<ruleset>        .*?)
                    (?:\s   --CONFIG--         \r?\n(?<config>         .*?))?
                    (?:\s   --SETTINGS--       \r?\n(?<settings>       .*?))?
                    (?:\s   --REQUIREMENTS--   \r?\n(?<requirements>   .*?))?
                    (?:\s   --EXPECT--         \r?\n(?<expect>         .*?\r?\n*))?
                    (?:\s   --INPUT--          \r?\n(?<input>          .*))?
                $/sx',
                $file->getContents(),
                $match
            )) {
                throw new \InvalidArgumentException('File format is invalid.');
            }

            $match = array_merge(
                [
                    'config' => null,
                    'settings' => null,
                    'requirements' => null,
                    'expect' => null,
                    'input' => null,
                ],
                $match
            );

            return new IntegrationCase(
                $file->getRelativePathname(),
                $this->determineTitle($file, $match['title']),
                $this->determineSettings($file, $match['settings']),
                $this->determineRequirements($file, $match['requirements']),
                $this->determineConfig($file, $match['config']),
                $this->determineRuleset($file, $match['ruleset']),
                $this->determineExpectedCode($file, $match['expect']),
                $this->determineInputCode($file, $match['input'])
            );
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(
                sprintf('%s Test file: "%s".', $e->getMessage(), $file->getRelativePathname()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Parses the '--CONFIG--' block of a '.test' file.
     *
     * @param string $config
     *
     * @return array
     */
    protected function determineConfig(SplFileInfo $file, $config)
    {
        $parsed = $this->parseJson($config, [
            'indent' => '    ',
            'lineEnding' => "\n",
        ]);

        if (!\is_string($parsed['indent'])) {
            throw new \InvalidArgumentException(sprintf(
                'Expected string value for "indent", got "%s".',
                \is_object($parsed['indent']) ? \get_class($parsed['indent']) : \gettype($parsed['indent']).'#'.$parsed['indent']
            ));
        }

        if (!\is_string($parsed['lineEnding'])) {
            throw new \InvalidArgumentException(sprintf(
                'Expected string value for "lineEnding", got "%s".',
                \is_object($parsed['lineEnding']) ? \get_class($parsed['lineEnding']) : \gettype($parsed['lineEnding']).'#'.$parsed['lineEnding']
            ));
        }

        return $parsed;
    }

    /**
     * Parses the '--REQUIREMENTS--' block of a '.test' file and determines requirements.
     *
     * @param string $config
     *
     * @return array
     */
    protected function determineRequirements(SplFileInfo $file, $config)
    {
        $parsed = $this->parseJson($config, [
            'php' => \PHP_VERSION_ID,
        ]);

        if (!\is_int($parsed['php'])) {
            throw new \InvalidArgumentException(sprintf(
                'Expected int value like 50509 for "php", got "%s".',
                \is_object($parsed['php']) ? \get_class($parsed['php']) : \gettype($parsed['php']).'#'.$parsed['php']
            ));
        }

        return $parsed;
    }

    /**
     * Parses the '--RULESET--' block of a '.test' file and determines what fixers should be used.
     *
     * @param string $config
     *
     * @return RuleSet
     */
    protected function determineRuleset(SplFileInfo $file, $config)
    {
        return new RuleSet($this->parseJson($config));
    }

    /**
     * Parses the '--TEST--' block of a '.test' file and determines title.
     *
     * @param string $config
     *
     * @return string
     */
    protected function determineTitle(SplFileInfo $file, $config)
    {
        return $config;
    }

    /**
     * Parses the '--SETTINGS--' block of a '.test' file and determines settings.
     *
     * @param string $config
     *
     * @return array
     */
    protected function determineSettings(SplFileInfo $file, $config)
    {
        $parsed = $this->parseJson($config, [
            'checkPriority' => true,
        ]);

        if (!\is_bool($parsed['checkPriority'])) {
            throw new \InvalidArgumentException(sprintf(
                'Expected bool value for "checkPriority", got "%s".',
                \is_object($parsed['checkPriority']) ? \get_class($parsed['checkPriority']) : \gettype($parsed['checkPriority']).'#'.$parsed['checkPriority']
            ));
        }

        return $parsed;
    }

    /**
     * @param null|string $code
     *
     * @return string
     */
    protected function determineExpectedCode(SplFileInfo $file, $code)
    {
        $code = $this->determineCode($file, $code, '-out.php');

        if (null === $code) {
            throw new \InvalidArgumentException('Missing expected code.');
        }

        return $code;
    }

    /**
     * @param null|string $code
     *
     * @return null|string
     */
    protected function determineInputCode(SplFileInfo $file, $code)
    {
        return $this->determineCode($file, $code, '-in.php');
    }

    /**
     * @param null|string $code
     * @param string      $suffix
     *
     * @return null|string
     */
    private function determineCode(SplFileInfo $file, $code, $suffix)
    {
        if (null !== $code) {
            return $code;
        }

        $candidateFile = new SplFileInfo($file->getPathname().$suffix, '', '');
        if ($candidateFile->isFile()) {
            return $candidateFile->getContents();
        }

        return null;
    }

    /**
     * @param null|string $encoded
     *
     * @return array
     */
    private function parseJson($encoded, array $template = null)
    {
        // content is optional if template is provided
        if (!$encoded && null !== $template) {
            $decoded = [];
        } else {
            $decoded = json_decode($encoded, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(sprintf('Malformed JSON: "%s", error: "%s".', $encoded, json_last_error_msg()));
            }
        }

        if (null !== $template) {
            $decoded = array_merge(
                $template,
                array_intersect_key(
                    $decoded,
                    array_flip(array_keys($template))
                )
            );
        }

        return $decoded;
    }
}
