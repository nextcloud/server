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
 * @author Graham Campbell <graham@alt-three.com>
 *
 * @deprecated
 */
final class AlignEqualsFixerHelper extends AbstractAlignFixerHelper
{
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

            if ($token->equals('=')) {
                $tokens[$index] = new Token(sprintf(self::ALIGNABLE_PLACEHOLDER, $this->deepestLevel).$token->getContent());

                continue;
            }

            if ($token->isGivenKind(T_FUNCTION)) {
                ++$this->deepestLevel;

                continue;
            }

            if ($token->equals('(')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);

                continue;
            }

            if ($token->equals('[')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE, $index);

                continue;
            }

            if ($token->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index);

                continue;
            }
        }
    }
}
