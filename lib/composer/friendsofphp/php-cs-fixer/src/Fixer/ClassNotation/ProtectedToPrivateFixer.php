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
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 * @author SpacePossum
 */
final class ProtectedToPrivateFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Converts `protected` variables and methods to `private` where possible.',
            [
                new CodeSample(
                    '<?php
final class Sample
{
    protected $a;

    protected function test()
    {
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
     * Must run before OrderedClassElementsFixer.
     */
    public function getPriority()
    {
        return 66;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAllTokenKindsFound([T_CLASS, T_FINAL, T_PROTECTED]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $end = \count($tokens) - 3; // min. number of tokens to form a class candidate to fix
        for ($index = 0; $index < $end; ++$index) {
            if (!$tokens[$index]->isGivenKind(T_CLASS)) {
                continue;
            }

            $classOpen = $tokens->getNextTokenOfKind($index, ['{']);
            $classClose = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $classOpen);

            if (!$this->skipClass($tokens, $index, $classOpen, $classClose)) {
                $this->fixClass($tokens, $classOpen, $classClose);
            }

            $index = $classClose;
        }
    }

    /**
     * @param int $classOpenIndex
     * @param int $classCloseIndex
     */
    private function fixClass(Tokens $tokens, $classOpenIndex, $classCloseIndex)
    {
        for ($index = $classOpenIndex + 1; $index < $classCloseIndex; ++$index) {
            if ($tokens[$index]->equals('{')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);

                continue;
            }

            if (!$tokens[$index]->isGivenKind(T_PROTECTED)) {
                continue;
            }

            $tokens[$index] = new Token([T_PRIVATE, 'private']);
        }
    }

    /**
     * Decide whether or not skip the fix for given class.
     *
     * @param int $classIndex
     * @param int $classOpenIndex
     * @param int $classCloseIndex
     *
     * @return bool
     */
    private function skipClass(Tokens $tokens, $classIndex, $classOpenIndex, $classCloseIndex)
    {
        $prevToken = $tokens[$tokens->getPrevMeaningfulToken($classIndex)];
        if (!$prevToken->isGivenKind(T_FINAL)) {
            return true;
        }

        for ($index = $classIndex; $index < $classOpenIndex; ++$index) {
            if ($tokens[$index]->isGivenKind(T_EXTENDS)) {
                return true;
            }
        }

        $useIndex = $tokens->getNextTokenOfKind($classIndex, [[CT::T_USE_TRAIT]]);

        return $useIndex && $useIndex < $classCloseIndex;
    }
}
