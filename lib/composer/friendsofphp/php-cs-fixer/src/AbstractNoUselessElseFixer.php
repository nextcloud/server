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

namespace PhpCsFixer;

use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
abstract class AbstractNoUselessElseFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // should be run before NoWhitespaceInBlankLineFixer, NoExtraBlankLinesFixer, BracesFixer and after NoEmptyStatementFixer.
        return 25;
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    protected function isSuperfluousElse(Tokens $tokens, $index)
    {
        $previousBlockStart = $index;
        do {
            // Check if all 'if', 'else if ' and 'elseif' blocks above this 'else' always end,
            // if so this 'else' is overcomplete.
            list($previousBlockStart, $previousBlockEnd) = $this->getPreviousBlock($tokens, $previousBlockStart);

            // short 'if' detection
            $previous = $previousBlockEnd;
            if ($tokens[$previous]->equals('}')) {
                $previous = $tokens->getPrevMeaningfulToken($previous);
            }

            if (
                !$tokens[$previous]->equals(';') ||                              // 'if' block doesn't end with semicolon, keep 'else'
                $tokens[$tokens->getPrevMeaningfulToken($previous)]->equals('{') // empty 'if' block, keep 'else'
            ) {
                return false;
            }

            $candidateIndex = $tokens->getPrevTokenOfKind(
                $previous,
                [
                    ';',
                    [T_BREAK],
                    [T_CLOSE_TAG],
                    [T_CONTINUE],
                    [T_EXIT],
                    [T_GOTO],
                    [T_IF],
                    [T_RETURN],
                    [T_THROW],
                ]
            );

            if (
                null === $candidateIndex
                || $tokens[$candidateIndex]->equalsAny([';', [T_CLOSE_TAG], [T_IF]])
                || $this->isInConditional($tokens, $candidateIndex, $previousBlockStart)
                || $this->isInConditionWithoutBraces($tokens, $candidateIndex, $previousBlockStart)
            ) {
                return false;
            }

            // implicit continue, i.e. delete candidate
        } while (!$tokens[$previousBlockStart]->isGivenKind(T_IF));

        return true;
    }

    /**
     * Return the first and last token index of the previous block.
     *
     * [0] First is either T_IF, T_ELSE or T_ELSEIF
     * [1] Last is either '}' or ';' / T_CLOSE_TAG for short notation blocks
     *
     * @param int $index T_IF, T_ELSE, T_ELSEIF
     *
     * @return int[]
     */
    private function getPreviousBlock(Tokens $tokens, $index)
    {
        $close = $previous = $tokens->getPrevMeaningfulToken($index);
        // short 'if' detection
        if ($tokens[$close]->equals('}')) {
            $previous = $tokens->findBlockStart(Tokens::BLOCK_TYPE_CURLY_BRACE, $close);
        }

        $open = $tokens->getPrevTokenOfKind($previous, [[T_IF], [T_ELSE], [T_ELSEIF]]);
        if ($tokens[$open]->isGivenKind(T_IF)) {
            $elseCandidate = $tokens->getPrevMeaningfulToken($open);
            if ($tokens[$elseCandidate]->isGivenKind(T_ELSE)) {
                $open = $elseCandidate;
            }
        }

        return [$open, $close];
    }

    /**
     * @param int $index           Index of the token to check
     * @param int $lowerLimitIndex Lower limit index. Since the token to check will always be in a conditional we must stop checking at this index
     *
     * @return bool
     */
    private function isInConditional(Tokens $tokens, $index, $lowerLimitIndex)
    {
        $candidateIndex = $tokens->getPrevTokenOfKind($index, [')', ';', ':']);
        if ($tokens[$candidateIndex]->equals(':')) {
            return true;
        }

        if (!$tokens[$candidateIndex]->equals(')')) {
            return false; // token is ';' or close tag
        }

        // token is always ')' here.
        // If it is part of the condition the token is always in, return false.
        // If it is not it is a nested condition so return true
        $open = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $candidateIndex);

        return $tokens->getPrevMeaningfulToken($open) > $lowerLimitIndex;
    }

    /**
     * For internal use only, as it is not perfect.
     *
     * Returns if the token at given index is part of a if/elseif/else statement
     * without {}. Assumes not passing the last `;`/close tag of the statement, not
     * out of range index, etc.
     *
     * @param int $index           Index of the token to check
     * @param int $lowerLimitIndex
     *
     * @return bool
     */
    private function isInConditionWithoutBraces(Tokens $tokens, $index, $lowerLimitIndex)
    {
        do {
            if ($tokens[$index]->isComment() || $tokens[$index]->isWhitespace()) {
                $index = $tokens->getPrevMeaningfulToken($index);
            }

            $token = $tokens[$index];
            if ($token->isGivenKind([T_IF, T_ELSEIF, T_ELSE])) {
                return true;
            }

            if ($token->equals(';')) {
                return false;
            }
            if ($token->equals('{')) {
                $index = $tokens->getPrevMeaningfulToken($index);

                // OK if belongs to: for, do, while, foreach
                // Not OK if belongs to: if, else, elseif
                if ($tokens[$index]->isGivenKind(T_DO)) {
                    --$index;

                    continue;
                }

                if (!$tokens[$index]->equals(')')) {
                    return false; // like `else {`
                }

                $index = $tokens->findBlockStart(
                    Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
                    $index
                );

                $index = $tokens->getPrevMeaningfulToken($index);
                if ($tokens[$index]->isGivenKind([T_IF, T_ELSEIF])) {
                    return false;
                }
            } elseif ($token->equals(')')) {
                $type = Tokens::detectBlockType($token);
                $index = $tokens->findBlockStart(
                    $type['type'],
                    $index
                );

                $index = $tokens->getPrevMeaningfulToken($index);
            } else {
                --$index;
            }
        } while ($index > $lowerLimitIndex);

        return false;
    }
}
