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
 * Transform `array` typehint from T_ARRAY into CT::T_ARRAY_TYPEHINT.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class ArrayTypehintTransformer extends AbstractTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getCustomTokens()
    {
        return [CT::T_ARRAY_TYPEHINT];
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
        if (!$token->isGivenKind(T_ARRAY)) {
            return;
        }

        $nextIndex = $tokens->getNextMeaningfulToken($index);
        $nextToken = $tokens[$nextIndex];

        if (!$nextToken->equals('(')) {
            $tokens[$index] = new Token([CT::T_ARRAY_TYPEHINT, $token->getContent()]);
        }
    }
}
