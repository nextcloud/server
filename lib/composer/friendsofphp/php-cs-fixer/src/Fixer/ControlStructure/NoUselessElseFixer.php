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

use PhpCsFixer\AbstractNoUselessElseFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class NoUselessElseFixer extends AbstractNoUselessElseFixer
{
    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_ELSE);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'There should not be useless `else` cases.',
            [
                new CodeSample("<?php\nif (\$a) {\n    return 1;\n} else {\n    return 2;\n}\n"),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before BracesFixer, CombineConsecutiveUnsetsFixer, NoExtraBlankLinesFixer, NoTrailingWhitespaceFixer, NoUselessReturnFixer, NoWhitespaceInBlankLineFixer.
     * Must run after NoAlternativeSyntaxFixer, NoEmptyStatementFixer, NoUnneededCurlyBracesFixer.
     */
    public function getPriority()
    {
        return parent::getPriority();
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_ELSE)) {
                continue;
            }

            // `else if` vs. `else` and alternative syntax `else:` checks
            if ($tokens[$tokens->getNextMeaningfulToken($index)]->equalsAny([':', [T_IF]])) {
                continue;
            }

            // clean up `else` if it is an empty statement
            $this->fixEmptyElse($tokens, $index);
            if ($tokens->isEmptyAt($index)) {
                continue;
            }

            // clean up `else` if possible
            if ($this->isSuperfluousElse($tokens, $index)) {
                $this->clearElse($tokens, $index);
            }
        }
    }

    /**
     * Remove tokens part of an `else` statement if not empty (i.e. no meaningful tokens inside).
     *
     * @param int $index T_ELSE index
     */
    private function fixEmptyElse(Tokens $tokens, $index)
    {
        $next = $tokens->getNextMeaningfulToken($index);
        if ($tokens[$next]->equals('{')) {
            $close = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $next);
            if (1 === $close - $next) { // '{}'
                $this->clearElse($tokens, $index);
            } elseif ($tokens->getNextMeaningfulToken($next) === $close) { // '{/**/}'
                $this->clearElse($tokens, $index);
            }

            return;
        }

        // short `else`
        $end = $tokens->getNextTokenOfKind($index, [';', [T_CLOSE_TAG]]);
        if ($next === $end) {
            $this->clearElse($tokens, $index);
        }
    }

    /**
     * @param int $index index of T_ELSE
     */
    private function clearElse(Tokens $tokens, $index)
    {
        $tokens->clearTokenAndMergeSurroundingWhitespace($index);

        // clear T_ELSE and the '{' '}' if there are any
        $next = $tokens->getNextMeaningfulToken($index);
        if (!$tokens[$next]->equals('{')) {
            return;
        }

        $tokens->clearTokenAndMergeSurroundingWhitespace($tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $next));
        $tokens->clearTokenAndMergeSurroundingWhitespace($next);
    }
}
