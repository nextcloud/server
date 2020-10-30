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

namespace PhpCsFixer\Tokenizer\Analyzer;

use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceUseAnalysis;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @internal
 */
final class NamespaceUsesAnalyzer
{
    /**
     * @return NamespaceUseAnalysis[]
     */
    public function getDeclarationsFromTokens(Tokens $tokens)
    {
        $tokenAnalyzer = new TokensAnalyzer($tokens);
        $useIndexes = $tokenAnalyzer->getImportUseIndexes();

        return $this->getDeclarations($tokens, $useIndexes);
    }

    /**
     * @return NamespaceUseAnalysis[]
     */
    private function getDeclarations(Tokens $tokens, array $useIndexes)
    {
        $uses = [];

        foreach ($useIndexes as $index) {
            $endIndex = $tokens->getNextTokenOfKind($index, [';', [T_CLOSE_TAG]]);
            $analysis = $this->parseDeclaration($tokens, $index, $endIndex);
            if ($analysis) {
                $uses[] = $analysis;
            }
        }

        return $uses;
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return null|NamespaceUseAnalysis
     */
    private function parseDeclaration(Tokens $tokens, $startIndex, $endIndex)
    {
        $fullName = $shortName = '';
        $aliased = false;

        $type = NamespaceUseAnalysis::TYPE_CLASS;
        for ($i = $startIndex; $i <= $endIndex; ++$i) {
            $token = $tokens[$i];
            if ($token->equals(',') || $token->isGivenKind(CT::T_GROUP_IMPORT_BRACE_CLOSE)) {
                // do not touch group use declarations until the logic of this is added (for example: `use some\a\{ClassD};`)
                // ignore multiple use statements that should be split into few separate statements (for example: `use BarB, BarC as C;`)
                return null;
            }

            if ($token->isGivenKind(CT::T_FUNCTION_IMPORT)) {
                $type = NamespaceUseAnalysis::TYPE_FUNCTION;
            } elseif ($token->isGivenKind(CT::T_CONST_IMPORT)) {
                $type = NamespaceUseAnalysis::TYPE_CONSTANT;
            }

            if ($token->isWhitespace() || $token->isComment() || $token->isGivenKind(T_USE)) {
                continue;
            }

            if ($token->isGivenKind(T_STRING)) {
                $shortName = $token->getContent();
                if (!$aliased) {
                    $fullName .= $shortName;
                }
            } elseif ($token->isGivenKind(T_NS_SEPARATOR)) {
                $fullName .= $token->getContent();
            } elseif ($token->isGivenKind(T_AS)) {
                $aliased = true;
            }
        }

        return new NamespaceUseAnalysis(
            trim($fullName),
            $shortName,
            $aliased,
            $startIndex,
            $endIndex,
            $type
        );
    }
}
