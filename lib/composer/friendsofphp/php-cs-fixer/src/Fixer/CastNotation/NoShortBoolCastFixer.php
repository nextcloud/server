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

namespace PhpCsFixer\Fixer\CastNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class NoShortBoolCastFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     *
     * Must run before CastSpacesFixer.
     */
    public function getPriority()
    {
        return -9;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Short cast `bool` using double exclamation mark should not be used.',
            [new CodeSample("<?php\n\$a = !!\$b;\n")]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound('!');
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        for ($index = \count($tokens) - 1; $index > 1; --$index) {
            if ($tokens[$index]->equals('!')) {
                $index = $this->fixShortCast($tokens, $index);
            }
        }
    }

    /**
     * @param int $index
     *
     * @return int
     */
    private function fixShortCast(Tokens $tokens, $index)
    {
        for ($i = $index - 1; $i > 1; --$i) {
            if ($tokens[$i]->equals('!')) {
                $this->fixShortCastToBoolCast($tokens, $i, $index);

                break;
            }

            if (!$tokens[$i]->isComment() && !$tokens[$i]->isWhitespace()) {
                break;
            }
        }

        return $i;
    }

    /**
     * @param int $start
     * @param int $end
     */
    private function fixShortCastToBoolCast(Tokens $tokens, $start, $end)
    {
        for (; $start <= $end; ++$start) {
            if (
                !$tokens[$start]->isComment()
                && !($tokens[$start]->isWhitespace() && $tokens[$start - 1]->isComment())
            ) {
                $tokens->clearAt($start);
            }
        }

        $tokens->insertAt($start, new Token([T_BOOL_CAST, '(bool)']));
    }
}
