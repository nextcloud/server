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

namespace PhpCsFixer\Fixer\Operator;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class TernaryOperatorSpacesFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Standardize spaces around ternary operator.',
            [new CodeSample("<?php \$a = \$a   ?1 :0;\n")]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after ArraySyntaxFixer, ListSyntaxFixer.
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
        return $tokens->isAllTokenKindsFound(['?', ':']);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $ternaryLevel = 0;

        foreach ($tokens as $index => $token) {
            if ($token->equals('?')) {
                ++$ternaryLevel;

                $nextNonWhitespaceIndex = $tokens->getNextNonWhitespace($index);
                $nextNonWhitespaceToken = $tokens[$nextNonWhitespaceIndex];

                if ($nextNonWhitespaceToken->equals(':')) {
                    // for `$a ?: $b` remove spaces between `?` and `:`
                    if ($tokens[$index + 1]->isWhitespace()) {
                        $tokens->clearAt($index + 1);
                    }
                } else {
                    // for `$a ? $b : $c` ensure space after `?`
                    $this->ensureWhitespaceExistence($tokens, $index + 1, true);
                }

                // for `$a ? $b : $c` ensure space before `?`
                $this->ensureWhitespaceExistence($tokens, $index - 1, false);

                continue;
            }

            if ($ternaryLevel && $token->equals(':')) {
                // for `$a ? $b : $c` ensure space after `:`
                $this->ensureWhitespaceExistence($tokens, $index + 1, true);

                $prevNonWhitespaceToken = $tokens[$tokens->getPrevNonWhitespace($index)];

                if (!$prevNonWhitespaceToken->equals('?')) {
                    // for `$a ? $b : $c` ensure space before `:`
                    $this->ensureWhitespaceExistence($tokens, $index - 1, false);
                }

                --$ternaryLevel;
            }
        }
    }

    /**
     * @param int  $index
     * @param bool $after
     */
    private function ensureWhitespaceExistence(Tokens $tokens, $index, $after)
    {
        if ($tokens[$index]->isWhitespace()) {
            if (
                false === strpos($tokens[$index]->getContent(), "\n")
                && !$tokens[$index - 1]->isComment()
            ) {
                $tokens[$index] = new Token([T_WHITESPACE, ' ']);
            }

            return;
        }

        $index += $after ? 0 : 1;
        $tokens->insertAt($index, new Token([T_WHITESPACE, ' ']));
    }
}
