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

namespace PhpCsFixer\Fixer\FunctionNotation;

use PhpCsFixer\AbstractFopenFlagFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class FopenFlagOrderFixer extends AbstractFopenFlagFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Order the flags in `fopen` calls, `b` and `t` must be last.',
            [new CodeSample("<?php\n\$a = fopen(\$foo, 'br+');\n")],
            null,
            'Risky when the function `fopen` is overridden.'
        );
    }

    /**
     * @param int $argumentStartIndex
     * @param int $argumentEndIndex
     */
    protected function fixFopenFlagToken(Tokens $tokens, $argumentStartIndex, $argumentEndIndex)
    {
        $argumentFlagIndex = null;

        for ($i = $argumentStartIndex; $i <= $argumentEndIndex; ++$i) {
            if ($tokens[$i]->isGivenKind([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            if (null !== $argumentFlagIndex) {
                return; // multiple meaningful tokens found, no candidate for fixing
            }

            $argumentFlagIndex = $i;
        }

        // check if second argument is candidate
        if (null === $argumentFlagIndex || !$tokens[$argumentFlagIndex]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
            return;
        }

        $content = $tokens[$argumentFlagIndex]->getContent();
        $contentQuote = $content[0]; // `'`, `"`, `b` or `B`

        if ('b' === $contentQuote || 'B' === $contentQuote) {
            $binPrefix = $contentQuote;
            $contentQuote = $content[1]; // `'` or `"`
            $mode = substr($content, 2, -1);
        } else {
            $binPrefix = '';
            $mode = substr($content, 1, -1);
        }

        $modeLength = \strlen($mode);
        if ($modeLength < 2) {
            return; // nothing to sort
        }

        if (false === $this->isValidModeString($mode)) {
            return;
        }

        $split = $this->sortFlags(Preg::split('#([^\+]\+?)#', $mode, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE));
        $newContent = $binPrefix.$contentQuote.implode('', $split).$contentQuote;

        if ($content !== $newContent) {
            $tokens[$argumentFlagIndex] = new Token([T_CONSTANT_ENCAPSED_STRING, $newContent]);
        }
    }

    /**
     * @param string[] $flags
     *
     * @return string[]
     */
    private function sortFlags(array $flags)
    {
        usort(
            $flags,
            static function ($flag1, $flag2) {
                if ($flag1 === $flag2) {
                    return 0;
                }

                if ('b' === $flag1) {
                    return 1;
                }

                if ('b' === $flag2) {
                    return -1;
                }

                if ('t' === $flag1) {
                    return 1;
                }

                if ('t' === $flag2) {
                    return -1;
                }

                return $flag1 < $flag2 ? -1 : 1;
            }
        );

        return $flags;
    }
}
