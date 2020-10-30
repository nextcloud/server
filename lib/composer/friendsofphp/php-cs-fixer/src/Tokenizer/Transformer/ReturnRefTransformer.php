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
 * Transform `&` operator into CT::T_RETURN_REF in `function & foo() {}`.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class ReturnRefTransformer extends AbstractTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getCustomTokens()
    {
        return [CT::T_RETURN_REF];
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
        $prevKinds = [T_FUNCTION];
        if (\PHP_VERSION_ID >= 70400) {
            $prevKinds[] = T_FN;
        }

        if (
            $token->equals('&')
            && $tokens[$tokens->getPrevMeaningfulToken($index)]->isGivenKind($prevKinds)
        ) {
            $tokens[$index] = new Token([CT::T_RETURN_REF, '&']);
        }
    }
}
