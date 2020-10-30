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
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpUnitExpectationFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, WhitespacesAwareFixerInterface
{
    /**
     * @var array<string, string>
     */
    private $methodMap = [];

    /**
     * {@inheritdoc}
     */
    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        $this->methodMap = [
            'setExpectedException' => 'expectExceptionMessage',
        ];

        if (PhpUnitTargetVersion::fulfills($this->configuration['target'], PhpUnitTargetVersion::VERSION_5_6)) {
            $this->methodMap['setExpectedExceptionRegExp'] = 'expectExceptionMessageRegExp';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Usages of `->setExpectedException*` methods MUST be replaced by `->expectException*` methods.',
            [
                new CodeSample(
                    '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testFoo()
    {
        $this->setExpectedException("RuntimeException", "Msg", 123);
        foo();
    }

    public function testBar()
    {
        $this->setExpectedExceptionRegExp("RuntimeException", "/Msg.*/", 123);
        bar();
    }
}
'
                ),
                new CodeSample(
                    '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testFoo()
    {
        $this->setExpectedException("RuntimeException", null, 123);
        foo();
    }

    public function testBar()
    {
        $this->setExpectedExceptionRegExp("RuntimeException", "/Msg.*/", 123);
        bar();
    }
}
',
                    ['target' => PhpUnitTargetVersion::VERSION_5_6]
                ),
                new CodeSample(
                    '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testFoo()
    {
        $this->setExpectedException("RuntimeException", "Msg", 123);
        foo();
    }

    public function testBar()
    {
        $this->setExpectedExceptionRegExp("RuntimeException", "/Msg.*/", 123);
        bar();
    }
}
',
                    ['target' => PhpUnitTargetVersion::VERSION_5_2]
                ),
            ],
            null,
            'Risky when PHPUnit classes are overridden or not accessible, or when project has PHPUnit incompatibilities.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after PhpUnitNoExpectationAnnotationFixer.
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
        return $tokens->isTokenKindFound(T_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            $this->fixExpectation($tokens, $indexes[0], $indexes[1]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('target', 'Target version of PHPUnit.'))
                ->setAllowedTypes(['string'])
                ->setAllowedValues([PhpUnitTargetVersion::VERSION_5_2, PhpUnitTargetVersion::VERSION_5_6, PhpUnitTargetVersion::VERSION_NEWEST])
                ->setDefault(PhpUnitTargetVersion::VERSION_NEWEST)
                ->getOption(),
        ]);
    }

    private function fixExpectation(Tokens $tokens, $startIndex, $endIndex)
    {
        $argumentsAnalyzer = new ArgumentsAnalyzer();

        $oldMethodSequence = [
            new Token([T_VARIABLE, '$this']),
            new Token([T_OBJECT_OPERATOR, '->']),
            [T_STRING],
        ];

        for ($index = $startIndex; $startIndex < $endIndex; ++$index) {
            $match = $tokens->findSequence($oldMethodSequence, $index);

            if (null === $match) {
                return;
            }

            list($thisIndex, , $index) = array_keys($match);

            if (!isset($this->methodMap[$tokens[$index]->getContent()])) {
                continue;
            }

            $openIndex = $tokens->getNextTokenOfKind($index, ['(']);
            $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openIndex);
            $commaIndex = $tokens->getPrevMeaningfulToken($closeIndex);
            if ($tokens[$commaIndex]->equals(',')) {
                $tokens->removeTrailingWhitespace($commaIndex);
                $tokens->clearAt($commaIndex);
            }

            $arguments = $argumentsAnalyzer->getArguments($tokens, $openIndex, $closeIndex);
            $argumentsCnt = \count($arguments);

            $argumentsReplacements = ['expectException', $this->methodMap[$tokens[$index]->getContent()], 'expectExceptionCode'];

            $indent = $this->whitespacesConfig->getLineEnding().$this->detectIndent($tokens, $thisIndex);

            $isMultilineWhitespace = false;

            for ($cnt = $argumentsCnt - 1; $cnt >= 1; --$cnt) {
                $argStart = array_keys($arguments)[$cnt];
                $argBefore = $tokens->getPrevMeaningfulToken($argStart);

                if ('expectExceptionMessage' === $argumentsReplacements[$cnt]) {
                    $paramIndicatorIndex = $tokens->getNextMeaningfulToken($argBefore);
                    $afterParamIndicatorIndex = $tokens->getNextMeaningfulToken($paramIndicatorIndex);

                    if (
                        $tokens[$paramIndicatorIndex]->equals([T_STRING, 'null'], false) &&
                        $tokens[$afterParamIndicatorIndex]->equals(')')
                    ) {
                        if ($tokens[$argBefore + 1]->isWhitespace()) {
                            $tokens->clearTokenAndMergeSurroundingWhitespace($argBefore + 1);
                        }
                        $tokens->clearTokenAndMergeSurroundingWhitespace($argBefore);
                        $tokens->clearTokenAndMergeSurroundingWhitespace($paramIndicatorIndex);

                        continue;
                    }
                }

                $isMultilineWhitespace = $isMultilineWhitespace || ($tokens[$argStart]->isWhitespace() && !$tokens[$argStart]->isWhitespace(" \t"));
                $tokensOverrideArgStart = [
                    new Token([T_WHITESPACE, $indent]),
                    new Token([T_VARIABLE, '$this']),
                    new Token([T_OBJECT_OPERATOR, '->']),
                    new Token([T_STRING, $argumentsReplacements[$cnt]]),
                    new Token('('),
                ];
                $tokensOverrideArgBefore = [
                    new Token(')'),
                    new Token(';'),
                ];

                if ($isMultilineWhitespace) {
                    array_push($tokensOverrideArgStart, new Token([T_WHITESPACE, $indent.$this->whitespacesConfig->getIndent()]));
                    array_unshift($tokensOverrideArgBefore, new Token([T_WHITESPACE, $indent]));
                }

                if ($tokens[$argStart]->isWhitespace()) {
                    $tokens->overrideRange($argStart, $argStart, $tokensOverrideArgStart);
                } else {
                    $tokens->insertAt($argStart, $tokensOverrideArgStart);
                }

                $tokens->overrideRange($argBefore, $argBefore, $tokensOverrideArgBefore);
            }

            $tokens[$index] = new Token([T_STRING, 'expectException']);
        }
    }

    /**
     * @param int $index
     *
     * @return string
     */
    private function detectIndent(Tokens $tokens, $index)
    {
        if (!$tokens[$index - 1]->isWhitespace()) {
            return ''; // cannot detect indent
        }

        $explodedContent = explode("\n", $tokens[$index - 1]->getContent());

        return end($explodedContent);
    }
}
