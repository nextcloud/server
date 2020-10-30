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

namespace PhpCsFixer\Fixer\ControlStructure;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Fixer for rules defined in PSR2 ¶5.1.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class ElseifFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'The keyword `elseif` should be used instead of `else if` so that all control keywords look like single words.',
            [new CodeSample("<?php\nif (\$a) {\n} else if (\$b) {\n}\n")]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before BracesFixer.
     * Must run after NoAlternativeSyntaxFixer.
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
        return $tokens->isAllTokenKindsFound([T_IF, T_ELSE]);
    }

    /**
     * Replace all `else if` (T_ELSE T_IF) with `elseif` (T_ELSEIF).
     *
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_ELSE)) {
                continue;
            }

            $ifTokenIndex = $tokens->getNextMeaningfulToken($index);

            // if next meaningful token is not T_IF - continue searching, this is not the case for fixing
            if (!$tokens[$ifTokenIndex]->isGivenKind(T_IF)) {
                continue;
            }

            // if next meaningful token is T_IF, but uses an alternative syntax - this is not the case for fixing neither
            $conditionEndBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $tokens->getNextMeaningfulToken($ifTokenIndex));
            $afterConditionIndex = $tokens->getNextMeaningfulToken($conditionEndBraceIndex);
            if ($tokens[$afterConditionIndex]->equals(':')) {
                continue;
            }

            // now we have T_ELSE following by T_IF with no alternative syntax so we could fix this
            // 1. clear whitespaces between T_ELSE and T_IF
            $tokens->clearAt($index + 1);

            // 2. change token from T_ELSE into T_ELSEIF
            $tokens[$index] = new Token([T_ELSEIF, 'elseif']);

            // 3. clear succeeding T_IF
            $tokens->clearAt($ifTokenIndex);

            $beforeIfTokenIndex = $tokens->getPrevNonWhitespace($ifTokenIndex);

            // 4. clear extra whitespace after T_IF in T_COMMENT,T_WHITESPACE?,T_IF,T_WHITESPACE sequence
            if ($tokens[$beforeIfTokenIndex]->isComment() && $tokens[$ifTokenIndex + 1]->isWhitespace()) {
                $tokens->clearAt($ifTokenIndex + 1);
            }
        }
    }
}
