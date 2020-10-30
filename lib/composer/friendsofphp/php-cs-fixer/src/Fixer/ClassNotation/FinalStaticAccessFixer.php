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

namespace PhpCsFixer\Fixer\ClassNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author ntzm
 */
final class FinalStaticAccessFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Converts `static` access to `self` access in `final` classes.',
            [
                new CodeSample(
                    '<?php
final class Sample
{
    public function getFoo()
    {
        return static::class;
    }
}
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after FinalInternalClassFixer, PhpUnitTestCaseStaticMethodCallsFixer.
     */
    public function getPriority()
    {
        return -1;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAllTokenKindsFound([T_FINAL, T_CLASS, T_STATIC]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        for ($index = $tokens->count() - 1; 0 <= $index; --$index) {
            if (!$tokens[$index]->isGivenKind(T_FINAL)) {
                continue;
            }

            $classTokenIndex = $tokens->getNextMeaningfulToken($index);

            if (!$tokens[$classTokenIndex]->isGivenKind(T_CLASS)) {
                continue;
            }

            $startClassIndex = $tokens->getNextTokenOfKind(
                $classTokenIndex,
                ['{']
            );

            $endClassIndex = $tokens->findBlockEnd(
                Tokens::BLOCK_TYPE_CURLY_BRACE,
                $startClassIndex
            );

            $this->replaceStaticAccessWithSelfAccessBetween(
                $tokens,
                $startClassIndex,
                $endClassIndex
            );
        }
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    private function replaceStaticAccessWithSelfAccessBetween(
        Tokens $tokens,
        $startIndex,
        $endIndex
    ) {
        for ($index = $startIndex; $index <= $endIndex; ++$index) {
            if ($tokens[$index]->isGivenKind(T_CLASS)) {
                $index = $this->getEndOfAnonymousClass($tokens, $index);

                continue;
            }

            if (!$tokens[$index]->isGivenKind(T_STATIC)) {
                continue;
            }

            $doubleColonIndex = $tokens->getNextMeaningfulToken($index);

            if (!$tokens[$doubleColonIndex]->isGivenKind(T_DOUBLE_COLON)) {
                continue;
            }

            $tokens[$index] = new Token([T_STRING, 'self']);
        }
    }

    /**
     * @param int $index
     *
     * @return int
     */
    private function getEndOfAnonymousClass(Tokens $tokens, $index)
    {
        $instantiationBraceStart = $tokens->getNextMeaningfulToken($index);

        if ($tokens[$instantiationBraceStart]->equals('(')) {
            $index = $tokens->findBlockEnd(
                Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
                $instantiationBraceStart
            );
        }

        $bodyBraceStart = $tokens->getNextTokenOfKind($index, ['{']);

        return $tokens->findBlockEnd(
            Tokens::BLOCK_TYPE_CURLY_BRACE,
            $bodyBraceStart
        );
    }
}
