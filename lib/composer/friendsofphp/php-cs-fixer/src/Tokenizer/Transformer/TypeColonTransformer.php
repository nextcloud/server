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
 * Transform `:` operator into CT::T_TYPE_COLON in `function foo() : int {}`.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class TypeColonTransformer extends AbstractTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getCustomTokens()
    {
        return [CT::T_TYPE_COLON];
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // needs to run after ReturnRefTransformer and UseTransformer
        return -10;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPhpVersionId()
    {
        return 70000;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Tokens $tokens, Token $token, $index)
    {
        if (!$token->equals(':')) {
            return;
        }

        $endIndex = $tokens->getPrevMeaningfulToken($index);

        if (!$tokens[$endIndex]->equals(')')) {
            return;
        }

        $startIndex = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $endIndex);
        $prevIndex = $tokens->getPrevMeaningfulToken($startIndex);
        $prevToken = $tokens[$prevIndex];

        // if this could be a function name we need to take one more step
        if ($prevToken->isGivenKind(T_STRING)) {
            $prevIndex = $tokens->getPrevMeaningfulToken($prevIndex);
            $prevToken = $tokens[$prevIndex];
        }

        $prevKinds = [T_FUNCTION, CT::T_RETURN_REF, CT::T_USE_LAMBDA];
        if (\PHP_VERSION_ID >= 70400) {
            $prevKinds[] = T_FN;
        }

        if ($prevToken->isGivenKind($prevKinds)) {
            $tokens[$index] = new Token([CT::T_TYPE_COLON, ':']);
        }
    }
}
