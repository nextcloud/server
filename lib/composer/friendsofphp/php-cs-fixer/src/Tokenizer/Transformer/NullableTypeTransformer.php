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
 * Transform `?` operator into CT::T_NULLABLE_TYPE in `function foo(?Bar $b) {}`.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class NullableTypeTransformer extends AbstractTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getCustomTokens()
    {
        return [CT::T_NULLABLE_TYPE];
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // needs to run after TypeColonTransformer
        return -20;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPhpVersionId()
    {
        return 70100;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Tokens $tokens, Token $token, $index)
    {
        if (!$token->equals('?')) {
            return;
        }

        $prevIndex = $tokens->getPrevMeaningfulToken($index);
        $prevToken = $tokens[$prevIndex];

        if ($prevToken->equalsAny(['(', ',', [CT::T_TYPE_COLON], [T_PRIVATE], [T_PROTECTED], [T_PUBLIC], [T_VAR], [T_STATIC]])) {
            $tokens[$index] = new Token([CT::T_NULLABLE_TYPE, '?']);
        }
    }
}
