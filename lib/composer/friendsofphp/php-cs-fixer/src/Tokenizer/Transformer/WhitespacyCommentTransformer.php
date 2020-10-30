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
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Move trailing whitespaces from comments and docs into following T_WHITESPACE token.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class WhitespacyCommentTransformer extends AbstractTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getCustomTokens()
    {
        return [];
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
        if (!$token->isComment()) {
            return;
        }

        $content = $token->getContent();
        $trimmedContent = rtrim($content);

        // nothing trimmed, nothing to do
        if ($content === $trimmedContent) {
            return;
        }

        $whitespaces = substr($content, \strlen($trimmedContent));

        $tokens[$index] = new Token([$token->getId(), $trimmedContent]);

        if (isset($tokens[$index + 1]) && $tokens[$index + 1]->isWhitespace()) {
            $tokens[$index + 1] = new Token([T_WHITESPACE, $whitespaces.$tokens[$index + 1]->getContent()]);
        } else {
            $tokens->insertAt($index + 1, new Token([T_WHITESPACE, $whitespaces]));
        }
    }
}
