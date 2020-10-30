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

namespace PhpCsFixer\Tokenizer\Transformer;

use PhpCsFixer\Tokenizer\AbstractTransformer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Transform discriminate overloaded square braces tokens.
 *
 * Performed transformations:
 * - in `[1, 2, 3]` into CT::T_ARRAY_SQUARE_BRACE_OPEN and CT::T_ARRAY_SQUARE_BRACE_CLOSE,
 * - in `[$a, &$b, [$c]] = array(1, 2, array(3))` into CT::T_DESTRUCTURING_SQUARE_BRACE_OPEN and CT::T_DESTRUCTURING_SQUARE_BRACE_CLOSE.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author SpacePossum
 *
 * @internal
 */
final class SquareBraceTransformer extends AbstractTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getCustomTokens()
    {
        return [
            CT::T_ARRAY_SQUARE_BRACE_OPEN,
            CT::T_ARRAY_SQUARE_BRACE_CLOSE,
            CT::T_DESTRUCTURING_SQUARE_BRACE_OPEN,
            CT::T_DESTRUCTURING_SQUARE_BRACE_CLOSE,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // must run after CurlyBraceTransformer
        return -1;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPhpVersionId()
    {
        // Short array syntax was introduced in PHP 5.4, but the fixer is smart
        // enough to handle it even before 5.4.
        // Same for array destructing syntax sugar `[` introduced in PHP 7.1.
        return 50000;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Tokens $tokens, Token $token, $index)
    {
        if ($this->isArrayDestructing($tokens, $index)) {
            $this->transformIntoDestructuringSquareBrace($tokens, $index);

            return;
        }

        if ($this->isShortArray($tokens, $index)) {
            $this->transformIntoArraySquareBrace($tokens, $index);
        }
    }

    /**
     * @param int $index
     */
    private function transformIntoArraySquareBrace(Tokens $tokens, $index)
    {
        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE, $index);

        $tokens[$index] = new Token([CT::T_ARRAY_SQUARE_BRACE_OPEN, '[']);
        $tokens[$endIndex] = new Token([CT::T_ARRAY_SQUARE_BRACE_CLOSE, ']']);
    }

    /**
     * @param int $index
     */
    private function transformIntoDestructuringSquareBrace(Tokens $tokens, $index)
    {
        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE, $index);

        $tokens[$index] = new Token([CT::T_DESTRUCTURING_SQUARE_BRACE_OPEN, '[']);
        $tokens[$endIndex] = new Token([CT::T_DESTRUCTURING_SQUARE_BRACE_CLOSE, ']']);

        $previousMeaningfulIndex = $index;
        $index = $tokens->getNextMeaningfulToken($index);

        while ($index < $endIndex) {
            if ($tokens[$index]->equals('[') && $tokens[$previousMeaningfulIndex]->equalsAny([[CT::T_DESTRUCTURING_SQUARE_BRACE_OPEN], ','])) {
                $tokens[$tokens->findBlockEnd(Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE, $index)] = new Token([CT::T_DESTRUCTURING_SQUARE_BRACE_CLOSE, ']']);
                $tokens[$index] = new Token([CT::T_DESTRUCTURING_SQUARE_BRACE_OPEN, '[']);
            }

            $previousMeaningfulIndex = $index;
            $index = $tokens->getNextMeaningfulToken($index);
        }
    }

    /**
     * Check if token under given index is short array opening.
     *
     * @param int $index
     *
     * @return bool
     */
    private function isShortArray(Tokens $tokens, $index)
    {
        if (!$tokens[$index]->equals('[')) {
            return false;
        }

        static $disallowedPrevTokens = [
            ')',
            ']',
            '}',
            '"',
            [T_CONSTANT_ENCAPSED_STRING],
            [T_STRING],
            [T_STRING_VARNAME],
            [T_VARIABLE],
            [CT::T_ARRAY_SQUARE_BRACE_CLOSE],
            [CT::T_DYNAMIC_PROP_BRACE_CLOSE],
            [CT::T_DYNAMIC_VAR_BRACE_CLOSE],
            [CT::T_ARRAY_INDEX_CURLY_BRACE_CLOSE],
        ];

        $prevToken = $tokens[$tokens->getPrevMeaningfulToken($index)];
        if ($prevToken->equalsAny($disallowedPrevTokens)) {
            return false;
        }

        $nextToken = $tokens[$tokens->getNextMeaningfulToken($index)];
        if ($nextToken->equals(']')) {
            return true;
        }

        return !$this->isArrayDestructing($tokens, $index);
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function isArrayDestructing(Tokens $tokens, $index)
    {
        if (\PHP_VERSION_ID < 70100 || !$tokens[$index]->equals('[')) {
            return false;
        }

        static $disallowedPrevTokens = [
            ')',
            ']',
            '"',
            [T_CONSTANT_ENCAPSED_STRING],
            [T_STRING],
            [T_STRING_VARNAME],
            [T_VARIABLE],
            [CT::T_ARRAY_SQUARE_BRACE_CLOSE],
            [CT::T_DYNAMIC_PROP_BRACE_CLOSE],
            [CT::T_DYNAMIC_VAR_BRACE_CLOSE],
            [CT::T_ARRAY_INDEX_CURLY_BRACE_CLOSE],
        ];

        $prevToken = $tokens[$tokens->getPrevMeaningfulToken($index)];
        if ($prevToken->equalsAny($disallowedPrevTokens)) {
            return false;
        }

        $type = Tokens::detectBlockType($tokens[$index]);
        $end = $tokens->findBlockEnd($type['type'], $index);

        $nextToken = $tokens[$tokens->getNextMeaningfulToken($end)];

        return $nextToken->equals('=');
    }
}
