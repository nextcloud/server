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
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @author Gert de Pagter
 */
final class PhpUnitSetUpTearDownVisibilityFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Changes the visibility of the `setUp()` and `tearDown()` functions of PHPUnit to `protected`, to match the PHPUnit TestCase.',
            [
                new CodeSample(
                    '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    private $hello;
    public function setUp()
    {
        $this->hello = "hello";
    }

    public function tearDown()
    {
        $this->hello = null;
    }
}
'
                ),
            ],
            null,
            'This fixer may change functions named `setUp()` or `tearDown()` outside of PHPUnit tests, '.
            'when a class is wrongly seen as a PHPUnit test.'
        );
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
            $this->fixSetUpAndTearDown($tokens, $indexes[0], $indexes[1]);
        }
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    private function fixSetUpAndTearDown(Tokens $tokens, $startIndex, $endIndex)
    {
        $counter = 0;
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        for ($i = $endIndex - 1; $i > $startIndex; --$i) {
            if (2 === $counter) {
                break; // we've seen both method we are interested in, so stop analyzing this class
            }

            if (!$this->isSetupOrTearDownMethod($tokens, $i)) {
                continue;
            }

            ++$counter;
            $visibility = $tokensAnalyzer->getMethodAttributes($i)['visibility'];

            if (T_PUBLIC === $visibility) {
                $index = $tokens->getPrevTokenOfKind($i, [[T_PUBLIC]]);
                $tokens[$index] = new Token([T_PROTECTED, 'protected']);

                continue;
            }

            if (null === $visibility) {
                $tokens->insertAt($i, [new Token([T_PROTECTED, 'protected']), new Token([T_WHITESPACE, ' '])]);
            }
        }
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function isSetupOrTearDownMethod(Tokens $tokens, $index)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        $isMethod = $tokens[$index]->isGivenKind(T_FUNCTION) && !$tokensAnalyzer->isLambda($index);
        if (!$isMethod) {
            return false;
        }

        $functionNameIndex = $tokens->getNextMeaningfulToken($index);
        $functionName = strtolower($tokens[$functionNameIndex]->getContent());

        return 'setup' === $functionName || 'teardown' === $functionName;
    }
}
