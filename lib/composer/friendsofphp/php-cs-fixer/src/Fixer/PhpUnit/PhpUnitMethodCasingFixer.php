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

namespace PhpCsFixer\Fixer\PhpUnit;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use PhpCsFixer\Utils;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class PhpUnitMethodCasingFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * @internal
     */
    const CAMEL_CASE = 'camel_case';

    /**
     * @internal
     */
    const SNAKE_CASE = 'snake_case';

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Enforce camel (or snake) case for PHPUnit test methods, following configuration.',
            [
                new CodeSample(
                    '<?php
class MyTest extends \\PhpUnit\\FrameWork\\TestCase
{
    public function test_my_code() {}
}
'
                ),
                new CodeSample(
                    '<?php
class MyTest extends \\PhpUnit\\FrameWork\\TestCase
{
    public function testMyCode() {}
}
',
                    ['case' => self::SNAKE_CASE]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after PhpUnitTestAnnotationFixer.
     */
    public function getPriority()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAllTokenKindsFound([T_CLASS, T_FUNCTION]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            $this->applyCasing($tokens, $indexes[0], $indexes[1]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('case', 'Apply camel or snake case to test methods'))
                ->setAllowedValues([self::CAMEL_CASE, self::SNAKE_CASE])
                ->setDefault(self::CAMEL_CASE)
                ->getOption(),
        ]);
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    private function applyCasing(Tokens $tokens, $startIndex, $endIndex)
    {
        for ($index = $endIndex - 1; $index > $startIndex; --$index) {
            if (!$this->isTestMethod($tokens, $index)) {
                continue;
            }

            $functionNameIndex = $tokens->getNextMeaningfulToken($index);
            $functionName = $tokens[$functionNameIndex]->getContent();
            $newFunctionName = $this->updateMethodCasing($functionName);

            if ($newFunctionName !== $functionName) {
                $tokens[$functionNameIndex] = new Token([T_STRING, $newFunctionName]);
            }

            $docBlockIndex = $this->getDocBlockIndex($tokens, $index);
            if ($this->hasDocBlock($tokens, $index)) {
                $this->updateDocBlock($tokens, $docBlockIndex);
            }
        }
    }

    /**
     * @param string $functionName
     *
     * @return string
     */
    private function updateMethodCasing($functionName)
    {
        if (self::CAMEL_CASE === $this->configuration['case']) {
            $newFunctionName = $functionName;
            $newFunctionName = ucwords($newFunctionName, '_');
            $newFunctionName = str_replace('_', '', $newFunctionName);
            $newFunctionName = lcfirst($newFunctionName);
        } else {
            $newFunctionName = Utils::camelCaseToUnderscore($functionName);
        }

        return $newFunctionName;
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function isTestMethod(Tokens $tokens, $index)
    {
        // Check if we are dealing with a (non abstract, non lambda) function
        if (!$this->isMethod($tokens, $index)) {
            return false;
        }

        // if the function name starts with test it's a test
        $functionNameIndex = $tokens->getNextMeaningfulToken($index);
        $functionName = $tokens[$functionNameIndex]->getContent();

        if ($this->startsWith('test', $functionName)) {
            return true;
        }
        // If the function doesn't have test in its name, and no doc block, it's not a test
        if (!$this->hasDocBlock($tokens, $index)) {
            return false;
        }

        $docBlockIndex = $this->getDocBlockIndex($tokens, $index);
        $doc = $tokens[$docBlockIndex]->getContent();
        if (false === strpos($doc, '@test')) {
            return false;
        }

        return true;
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function isMethod(Tokens $tokens, $index)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        return $tokens[$index]->isGivenKind(T_FUNCTION) && !$tokensAnalyzer->isLambda($index);
    }

    /**
     * @param string $needle
     * @param string $haystack
     *
     * @return bool
     */
    private function startsWith($needle, $haystack)
    {
        return substr($haystack, 0, \strlen($needle)) === $needle;
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function hasDocBlock(Tokens $tokens, $index)
    {
        $docBlockIndex = $this->getDocBlockIndex($tokens, $index);

        return $tokens[$docBlockIndex]->isGivenKind(T_DOC_COMMENT);
    }

    /**
     * @param int $index
     *
     * @return int
     */
    private function getDocBlockIndex(Tokens $tokens, $index)
    {
        do {
            $index = $tokens->getPrevNonWhitespace($index);
        } while ($tokens[$index]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE, T_FINAL, T_ABSTRACT, T_COMMENT]));

        return $index;
    }

    /**
     * @param int $docBlockIndex
     */
    private function updateDocBlock(Tokens $tokens, $docBlockIndex)
    {
        $doc = new DocBlock($tokens[$docBlockIndex]->getContent());
        $lines = $doc->getLines();

        $docBlockNeedsUpdate = false;
        for ($inc = 0; $inc < \count($lines); ++$inc) {
            $lineContent = $lines[$inc]->getContent();
            if (false === strpos($lineContent, '@depends')) {
                continue;
            }

            $newLineContent = Preg::replaceCallback('/(@depends\s+)(.+)(\b)/', function (array $matches) {
                return sprintf(
                    '%s%s%s',
                    $matches[1],
                    $this->updateMethodCasing($matches[2]),
                    $matches[3]
                );
            }, $lineContent);

            if ($newLineContent !== $lineContent) {
                $lines[$inc] = new Line($newLineContent);
                $docBlockNeedsUpdate = true;
            }
        }

        if ($docBlockNeedsUpdate) {
            $lines = implode('', $lines);
            $tokens[$docBlockIndex] = new Token([T_DOC_COMMENT, $lines]);
        }
    }
}
