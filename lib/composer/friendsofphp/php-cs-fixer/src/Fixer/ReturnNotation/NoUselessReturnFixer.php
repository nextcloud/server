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

namespace PhpCsFixer\Fixer\ReturnNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class NoUselessReturnFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAllTokenKindsFound([T_FUNCTION, T_RETURN]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'There should not be an empty `return` statement at the end of a function.',
            [
                new CodeSample(
                    '<?php
function example($b) {
    if ($b) {
        return;
    }
    return;
}
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before BlankLineBeforeReturnFixer, BlankLineBeforeStatementFixer, NoExtraBlankLinesFixer, NoWhitespaceInBlankLineFixer.
     * Must run after NoEmptyStatementFixer, NoUnneededCurlyBracesFixer, NoUselessElseFixer, SimplifiedNullReturnFixer.
     */
    public function getPriority()
    {
        return -18;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $index = $tokens->getNextTokenOfKind($index, [';', '{']);
            if ($tokens[$index]->equals('{')) {
                $this->fixFunction($tokens, $index, $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index));
            }
        }
    }

    /**
     * @param int $start Token index of the opening brace token of the function
     * @param int $end   Token index of the closing brace token of the function
     */
    private function fixFunction(Tokens $tokens, $start, $end)
    {
        for ($index = $end; $index > $start; --$index) {
            if (!$tokens[$index]->isGivenKind(T_RETURN)) {
                continue;
            }

            $nextAt = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$nextAt]->equals(';')) {
                continue;
            }

            if ($tokens->getNextMeaningfulToken($nextAt) !== $end) {
                continue;
            }

            $previous = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$previous]->equalsAny([[T_ELSE], ')'])) {
                continue;
            }

            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
            $tokens->clearTokenAndMergeSurroundingWhitespace($nextAt);
        }
    }
}
