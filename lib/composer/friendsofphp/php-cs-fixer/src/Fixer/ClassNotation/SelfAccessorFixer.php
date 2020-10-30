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

namespace PhpCsFixer\Fixer\ClassNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @author Gregor Harlan <gharlan@web.de>
 */
final class SelfAccessorFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Inside class or interface element `self` should be preferred to the class name itself.',
            [
                new CodeSample(
                    '<?php
class Sample
{
    const BAZ = 1;
    const BAR = Sample::BAZ;

    public function getBar()
    {
        return Sample::BAR;
    }
}
'
                ),
            ],
            null,
            'Risky when using dynamic calls like get_called_class() or late static binding.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_CLASS, T_INTERFACE]);
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
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        foreach ((new NamespacesAnalyzer())->getDeclarations($tokens) as $namespace) {
            for ($index = $namespace->getScopeStartIndex(); $index < $namespace->getScopeEndIndex(); ++$index) {
                if (!$tokens[$index]->isGivenKind([T_CLASS, T_INTERFACE]) || $tokensAnalyzer->isAnonymousClass($index)) {
                    continue;
                }

                $nameIndex = $tokens->getNextTokenOfKind($index, [[T_STRING]]);
                $startIndex = $tokens->getNextTokenOfKind($nameIndex, ['{']);
                $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startIndex);

                $name = $tokens[$nameIndex]->getContent();

                $this->replaceNameOccurrences($tokens, $namespace->getFullName(), $name, $startIndex, $endIndex);

                $index = $endIndex;
            }
        }
    }

    /**
     * Replace occurrences of the name of the classy element by "self" (if possible).
     *
     * @param string $namespace
     * @param string $name
     * @param int    $startIndex
     * @param int    $endIndex
     */
    private function replaceNameOccurrences(Tokens $tokens, $namespace, $name, $startIndex, $endIndex)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);
        $insideMethodSignatureUntil = null;

        for ($i = $startIndex; $i < $endIndex; ++$i) {
            if ($i === $insideMethodSignatureUntil) {
                $insideMethodSignatureUntil = null;
            }

            $token = $tokens[$i];

            // skip anonymous classes
            if ($token->isGivenKind(T_CLASS) && $tokensAnalyzer->isAnonymousClass($i)) {
                $i = $tokens->getNextTokenOfKind($i, ['{']);
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $i);

                continue;
            }

            if ($token->isGivenKind(T_FUNCTION)) {
                $i = $tokens->getNextTokenOfKind($i, ['(']);
                $insideMethodSignatureUntil = $tokens->getNextTokenOfKind($i, ['{', ';']);

                continue;
            }

            if (!$token->equals([T_STRING, $name], false)) {
                continue;
            }

            $nextToken = $tokens[$tokens->getNextMeaningfulToken($i)];
            if ($nextToken->isGivenKind(T_NS_SEPARATOR)) {
                continue;
            }

            $classStartIndex = $i;
            $prevToken = $tokens[$tokens->getPrevMeaningfulToken($i)];
            if ($prevToken->isGivenKind(T_NS_SEPARATOR)) {
                $classStartIndex = $this->getClassStart($tokens, $i, $namespace);
                if (null === $classStartIndex) {
                    continue;
                }
                $prevToken = $tokens[$tokens->getPrevMeaningfulToken($classStartIndex)];
            }
            if ($prevToken->isGivenKind([T_OBJECT_OPERATOR, T_STRING])) {
                continue;
            }

            if (
                $prevToken->isGivenKind([T_INSTANCEOF, T_NEW])
                || $nextToken->isGivenKind(T_PAAMAYIM_NEKUDOTAYIM)
                || (
                    null !== $insideMethodSignatureUntil
                    && $i < $insideMethodSignatureUntil
                    && $prevToken->equalsAny(['(', ',', [CT::T_TYPE_COLON], [CT::T_NULLABLE_TYPE]])
                )
            ) {
                for ($j = $classStartIndex; $j < $i; ++$j) {
                    $tokens->clearTokenAndMergeSurroundingWhitespace($j);
                }
                $tokens[$i] = new Token([T_STRING, 'self']);
            }
        }
    }

    private function getClassStart(Tokens $tokens, $index, $namespace)
    {
        $namespace = ('' !== $namespace ? '\\'.$namespace : '').'\\';

        foreach (array_reverse(Preg::split('/(\\\\)/', $namespace, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)) as $piece) {
            $index = $tokens->getPrevMeaningfulToken($index);
            if ('\\' === $piece) {
                if (!$tokens[$index]->isGivenKind(T_NS_SEPARATOR)) {
                    return null;
                }
            } elseif (!$tokens[$index]->equals([T_STRING, $piece], false)) {
                return null;
            }
        }

        return $index;
    }
}
