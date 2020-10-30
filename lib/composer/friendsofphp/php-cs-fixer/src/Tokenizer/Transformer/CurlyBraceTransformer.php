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
 * Transform discriminate overloaded curly braces tokens.
 *
 * Performed transformations:
 * - closing `}` for T_CURLY_OPEN into CT::T_CURLY_CLOSE,
 * - closing `}` for T_DOLLAR_OPEN_CURLY_BRACES into CT::T_DOLLAR_CLOSE_CURLY_BRACES,
 * - in `$foo->{$bar}` into CT::T_DYNAMIC_PROP_BRACE_OPEN and CT::T_DYNAMIC_PROP_BRACE_CLOSE,
 * - in `${$foo}` into CT::T_DYNAMIC_VAR_BRACE_OPEN and CT::T_DYNAMIC_VAR_BRACE_CLOSE,
 * - in `$array{$index}` into CT::T_ARRAY_INDEX_CURLY_BRACE_OPEN and CT::T_ARRAY_INDEX_CURLY_BRACE_CLOSE,
 * - in `use some\a\{ClassA, ClassB, ClassC as C}` into CT::T_GROUP_IMPORT_BRACE_OPEN, CT::T_GROUP_IMPORT_BRACE_CLOSE.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class CurlyBraceTransformer extends AbstractTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getCustomTokens()
    {
        return [
            CT::T_CURLY_CLOSE,
            CT::T_DOLLAR_CLOSE_CURLY_BRACES,
            CT::T_DYNAMIC_PROP_BRACE_OPEN,
            CT::T_DYNAMIC_PROP_BRACE_CLOSE,
            CT::T_DYNAMIC_VAR_BRACE_OPEN,
            CT::T_DYNAMIC_VAR_BRACE_CLOSE,
            CT::T_ARRAY_INDEX_CURLY_BRACE_OPEN,
            CT::T_ARRAY_INDEX_CURLY_BRACE_CLOSE,
            CT::T_GROUP_IMPORT_BRACE_OPEN,
            CT::T_GROUP_IMPORT_BRACE_CLOSE,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPhpVersionId()
    {
        return 50000;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Tokens $tokens, Token $token, $index)
    {
        $this->transformIntoCurlyCloseBrace($tokens, $token, $index);
        $this->transformIntoDollarCloseBrace($tokens, $token, $index);
        $this->transformIntoDynamicPropBraces($tokens, $token, $index);
        $this->transformIntoDynamicVarBraces($tokens, $token, $index);
        $this->transformIntoCurlyIndexBraces($tokens, $token, $index);

        if (\PHP_VERSION_ID >= 70000) {
            $this->transformIntoGroupUseBraces($tokens, $token, $index);
        }
    }

    /**
     * Transform closing `}` for T_CURLY_OPEN into CT::T_CURLY_CLOSE.
     *
     * This should be done at very beginning of curly braces transformations.
     *
     * @param int $index
     */
    private function transformIntoCurlyCloseBrace(Tokens $tokens, Token $token, $index)
    {
        if (!$token->isGivenKind(T_CURLY_OPEN)) {
            return;
        }

        $level = 1;
        $nestIndex = $index;

        while (0 < $level) {
            ++$nestIndex;

            // we count all kind of {
            if ($tokens[$nestIndex]->equals('{')) {
                ++$level;

                continue;
            }

            // we count all kind of }
            if ($tokens[$nestIndex]->equals('}')) {
                --$level;
            }
        }

        $tokens[$nestIndex] = new Token([CT::T_CURLY_CLOSE, '}']);
    }

    private function transformIntoDollarCloseBrace(Tokens $tokens, Token $token, $index)
    {
        if ($token->isGivenKind(T_DOLLAR_OPEN_CURLY_BRACES)) {
            $nextIndex = $tokens->getNextTokenOfKind($index, ['}']);
            $tokens[$nextIndex] = new Token([CT::T_DOLLAR_CLOSE_CURLY_BRACES, '}']);
        }
    }

    private function transformIntoDynamicPropBraces(Tokens $tokens, Token $token, $index)
    {
        if (!$token->isGivenKind(T_OBJECT_OPERATOR)) {
            return;
        }

        if (!$tokens[$index + 1]->equals('{')) {
            return;
        }

        $openIndex = $index + 1;
        $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openIndex);

        $tokens[$openIndex] = new Token([CT::T_DYNAMIC_PROP_BRACE_OPEN, '{']);
        $tokens[$closeIndex] = new Token([CT::T_DYNAMIC_PROP_BRACE_CLOSE, '}']);
    }

    private function transformIntoDynamicVarBraces(Tokens $tokens, Token $token, $index)
    {
        if (!$token->equals('$')) {
            return;
        }

        $openIndex = $tokens->getNextMeaningfulToken($index);

        if (null === $openIndex) {
            return;
        }

        $openToken = $tokens[$openIndex];

        if (!$openToken->equals('{')) {
            return;
        }

        $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openIndex);

        $tokens[$openIndex] = new Token([CT::T_DYNAMIC_VAR_BRACE_OPEN, '{']);
        $tokens[$closeIndex] = new Token([CT::T_DYNAMIC_VAR_BRACE_CLOSE, '}']);
    }

    private function transformIntoCurlyIndexBraces(Tokens $tokens, Token $token, $index)
    {
        if (!$token->equals('{')) {
            return;
        }

        $prevIndex = $tokens->getPrevMeaningfulToken($index);

        if (!$tokens[$prevIndex]->equalsAny([
            [T_STRING],
            [T_VARIABLE],
            [CT::T_ARRAY_INDEX_CURLY_BRACE_CLOSE],
            ']',
            ')',
        ])) {
            return;
        }

        if (
            $tokens[$prevIndex]->isGivenKind(T_STRING)
            && !$tokens[$tokens->getPrevMeaningfulToken($prevIndex)]->isGivenKind(T_OBJECT_OPERATOR)
        ) {
            return;
        }

        if (
            $tokens[$prevIndex]->equals(')')
            && !$tokens[$tokens->getPrevMeaningfulToken(
                $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $prevIndex)
            )]->isGivenKind(T_ARRAY)
        ) {
            return;
        }

        $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);

        $tokens[$index] = new Token([CT::T_ARRAY_INDEX_CURLY_BRACE_OPEN, '{']);
        $tokens[$closeIndex] = new Token([CT::T_ARRAY_INDEX_CURLY_BRACE_CLOSE, '}']);
    }

    private function transformIntoGroupUseBraces(Tokens $tokens, Token $token, $index)
    {
        if (!$token->equals('{')) {
            return;
        }

        $prevIndex = $tokens->getPrevMeaningfulToken($index);

        if (!$tokens[$prevIndex]->isGivenKind(T_NS_SEPARATOR)) {
            return;
        }

        $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);

        $tokens[$index] = new Token([CT::T_GROUP_IMPORT_BRACE_OPEN, '{']);
        $tokens[$closeIndex] = new Token([CT::T_GROUP_IMPORT_BRACE_CLOSE, '}']);
    }
}
