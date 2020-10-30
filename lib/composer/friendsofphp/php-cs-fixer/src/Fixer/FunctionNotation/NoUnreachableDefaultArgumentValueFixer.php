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

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Mark Scherer
 * @author Lucas Manzke <lmanzke@outlook.com>
 * @author Gregor Harlan <gharlan@web.de>
 */
final class NoUnreachableDefaultArgumentValueFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'In function arguments there must not be arguments with default values before non-default ones.',
            [
                new CodeSample(
                    '<?php
function example($foo = "two words", $bar) {}
'
                ),
            ],
            null,
            'Modifies the signature of functions; therefore risky when using systems (such as some Symfony components) that rely on those (for example through reflection).'
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after NullableTypeDeclarationForDefaultNullValueFixer.
     */
    public function getPriority()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        if (\PHP_VERSION_ID >= 70400 && $tokens->isTokenKindFound(T_FN)) {
            return true;
        }

        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        for ($i = 0, $l = $tokens->count(); $i < $l; ++$i) {
            if (
                !$tokens[$i]->isGivenKind(T_FUNCTION)
                && (\PHP_VERSION_ID < 70400 || !$tokens[$i]->isGivenKind(T_FN))
            ) {
                continue;
            }

            $startIndex = $tokens->getNextTokenOfKind($i, ['(']);
            $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startIndex);

            $this->fixFunctionDefinition($tokens, $startIndex, $i);
        }
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    private function fixFunctionDefinition(Tokens $tokens, $startIndex, $endIndex)
    {
        $lastArgumentIndex = $this->getLastNonDefaultArgumentIndex($tokens, $startIndex, $endIndex);

        if (!$lastArgumentIndex) {
            return;
        }

        for ($i = $lastArgumentIndex; $i > $startIndex; --$i) {
            $token = $tokens[$i];

            if ($token->isGivenKind(T_VARIABLE)) {
                $lastArgumentIndex = $i;

                continue;
            }

            if (!$token->equals('=') || $this->isNonNullableTypehintedNullableVariable($tokens, $i)) {
                continue;
            }

            $endIndex = $tokens->getPrevTokenOfKind($lastArgumentIndex, [',']);
            $endIndex = $tokens->getPrevMeaningfulToken($endIndex);
            $this->removeDefaultArgument($tokens, $i, $endIndex);
        }
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return null|int
     */
    private function getLastNonDefaultArgumentIndex(Tokens $tokens, $startIndex, $endIndex)
    {
        for ($i = $endIndex - 1; $i > $startIndex; --$i) {
            $token = $tokens[$i];

            if ($token->equals('=')) {
                $i = $tokens->getPrevMeaningfulToken($i);

                continue;
            }

            if ($token->isGivenKind(T_VARIABLE) && !$this->isEllipsis($tokens, $i)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param int $variableIndex
     *
     * @return bool
     */
    private function isEllipsis(Tokens $tokens, $variableIndex)
    {
        return $tokens[$tokens->getPrevMeaningfulToken($variableIndex)]->isGivenKind(T_ELLIPSIS);
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    private function removeDefaultArgument(Tokens $tokens, $startIndex, $endIndex)
    {
        for ($i = $startIndex; $i <= $endIndex;) {
            $tokens->clearTokenAndMergeSurroundingWhitespace($i);
            $this->clearWhitespacesBeforeIndex($tokens, $i);
            $i = $tokens->getNextMeaningfulToken($i);
        }
    }

    /**
     * @param int $index Index of "="
     *
     * @return bool
     */
    private function isNonNullableTypehintedNullableVariable(Tokens $tokens, $index)
    {
        $nextToken = $tokens[$tokens->getNextMeaningfulToken($index)];

        if (!$nextToken->equals([T_STRING, 'null'], false)) {
            return false;
        }

        $variableIndex = $tokens->getPrevMeaningfulToken($index);

        $searchTokens = [',', '(', [T_STRING], [CT::T_ARRAY_TYPEHINT], [T_CALLABLE]];
        $typehintKinds = [T_STRING, CT::T_ARRAY_TYPEHINT, T_CALLABLE];

        $prevIndex = $tokens->getPrevTokenOfKind($variableIndex, $searchTokens);

        if (!$tokens[$prevIndex]->isGivenKind($typehintKinds)) {
            return false;
        }

        return !$tokens[$tokens->getPrevMeaningfulToken($prevIndex)]->isGivenKind(CT::T_NULLABLE_TYPE);
    }

    /**
     * @param int $index
     */
    private function clearWhitespacesBeforeIndex(Tokens $tokens, $index)
    {
        $prevIndex = $tokens->getNonEmptySibling($index, -1);
        if (!$tokens[$prevIndex]->isWhitespace()) {
            return;
        }

        $prevNonWhiteIndex = $tokens->getPrevNonWhitespace($prevIndex);
        if (null === $prevNonWhiteIndex || !$tokens[$prevNonWhiteIndex]->isComment()) {
            $tokens->clearTokenAndMergeSurroundingWhitespace($prevIndex);
        }
    }
}
