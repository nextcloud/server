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

use PhpCsFixer\AbstractAlignFixerHelper;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Carlos Cirello <carlos.cirello.nl@gmail.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Graham Campbell <graham@alt-three.com>
 *
 * @deprecated
 */
final class AlignDoubleArrowFixerHelper extends AbstractAlignFixerHelper
{
    /**
     * Level counter of the current nest level.
     * So one level alignments are not mixed with
     * other level ones.
     *
     * @var int
     */
    private $currentLevel = 0;

    public function __construct()
    {
        @trigger_error(
            sprintf(
                'The "%s" class is deprecated. You should stop using it, as it will be removed in 3.0 version.',
                __CLASS__
            ),
            E_USER_DEPRECATED
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function injectAlignmentPlaceholders(Tokens $tokens, $startAt, $endAt)
    {
        for ($index = $startAt; $index < $endAt; ++$index) {
            $token = $tokens[$index];

            if ($token->isGivenKind([T_FOREACH, T_FOR, T_WHILE, T_IF, T_SWITCH])) {
                $index = $tokens->getNextMeaningfulToken($index);
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);

                continue;
            }

            if ($token->isGivenKind(T_ARRAY)) { // don't use "$tokens->isArray()" here, short arrays are handled in the next case
                $from = $tokens->getNextMeaningfulToken($index);
                $until = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $from);
                $index = $until;

                $this->injectArrayAlignmentPlaceholders($tokens, $from, $until);

                continue;
            }

            if ($token->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
                $prevToken = $tokens[$tokens->getPrevMeaningfulToken($index)];
                if ($prevToken->isGivenKind([T_STRING, T_VARIABLE])) {
                    continue;
                }

                $from = $index;
                $until = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $from);
                $index = $until;

                $this->injectArrayAlignmentPlaceholders($tokens, $from + 1, $until - 1);

                continue;
            }

            if ($token->isGivenKind(T_DOUBLE_ARROW)) {
                $tokenContent = sprintf(self::ALIGNABLE_PLACEHOLDER, $this->currentLevel).$token->getContent();

                $nextIndex = $index + 1;
                $nextToken = $tokens[$nextIndex];
                if (!$nextToken->isWhitespace()) {
                    $tokenContent .= ' ';
                } elseif ($nextToken->isWhitespace(" \t")) {
                    $tokens[$nextIndex] = new Token([T_WHITESPACE, ' ']);
                }

                $tokens[$index] = new Token([T_DOUBLE_ARROW, $tokenContent]);

                continue;
            }

            if ($token->equals(';')) {
                ++$this->deepestLevel;
                ++$this->currentLevel;

                continue;
            }

            if ($token->equals(',')) {
                for ($i = $index; $i < $endAt - 1; ++$i) {
                    if (false !== strpos($tokens[$i - 1]->getContent(), "\n")) {
                        break;
                    }

                    if ($tokens[$i + 1]->isGivenKind([T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN])) {
                        $arrayStartIndex = $tokens[$i + 1]->isGivenKind(T_ARRAY)
                            ? $tokens->getNextMeaningfulToken($i + 1)
                            : $i + 1
                        ;
                        $blockType = Tokens::detectBlockType($tokens[$arrayStartIndex]);
                        $arrayEndIndex = $tokens->findBlockEnd($blockType['type'], $arrayStartIndex);

                        if ($tokens->isPartialCodeMultiline($arrayStartIndex, $arrayEndIndex)) {
                            break;
                        }
                    }

                    ++$index;
                }
            }
        }
    }

    /**
     * @param int $from
     * @param int $until
     */
    private function injectArrayAlignmentPlaceholders(Tokens $tokens, $from, $until)
    {
        // Only inject placeholders for multi-line arrays
        if ($tokens->isPartialCodeMultiline($from, $until)) {
            ++$this->deepestLevel;
            ++$this->currentLevel;
            $this->injectAlignmentPlaceholders($tokens, $from, $until);
            --$this->currentLevel;
        }
    }
}
