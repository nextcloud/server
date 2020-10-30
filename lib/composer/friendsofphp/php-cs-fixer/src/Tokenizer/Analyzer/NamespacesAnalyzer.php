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

use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceAnalysis;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class NamespacesAnalyzer
{
    /**
     * @return NamespaceAnalysis[]
     */
    public function getDeclarations(Tokens $tokens)
    {
        $namespaces = [];

        for ($index = 1, $count = \count($tokens); $index < $count; ++$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_NAMESPACE)) {
                continue;
            }

            $declarationEndIndex = $tokens->getNextTokenOfKind($index, [';', '{']);
            $namespace = trim($tokens->generatePartialCode($index + 1, $declarationEndIndex - 1));
            $declarationParts = explode('\\', $namespace);
            $shortName = end($declarationParts);

            if ($tokens[$declarationEndIndex]->equals('{')) {
                $scopeEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $declarationEndIndex);
            } else {
                $scopeEndIndex = $tokens->getNextTokenOfKind($declarationEndIndex, [[T_NAMESPACE]]);
                if (null === $scopeEndIndex) {
                    $scopeEndIndex = \count($tokens);
                }
                --$scopeEndIndex;
            }

            $namespaces[] = new NamespaceAnalysis(
                $namespace,
                $shortName,
                $index,
                $declarationEndIndex,
                $index,
                $scopeEndIndex
            );

            // Continue the analysis after the end of this namespace to find the next one
            $index = $scopeEndIndex;
        }

        if (0 === \count($namespaces)) {
            $namespaces[] = new NamespaceAnalysis('', '', 0, 0, 0, \count($tokens) - 1);
        }

        return $namespaces;
    }
}
