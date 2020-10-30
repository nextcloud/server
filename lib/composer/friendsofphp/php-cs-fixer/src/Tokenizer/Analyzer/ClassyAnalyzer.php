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

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class ClassyAnalyzer
{
    /**
     * @param int $index
     *
     * @return bool
     */
    public function isClassyInvocation(Tokens $tokens, $index)
    {
        $token = $tokens[$index];

        if (!$token->isGivenKind(T_STRING)) {
            throw new \LogicException(sprintf('No T_STRING at given index %d, got %s.', $index, $tokens[$index]->getName()));
        }

        if (\in_array(strtolower($token->getContent()), ['bool', 'float', 'int', 'iterable', 'object', 'parent', 'self', 'string', 'void'], true)) {
            return false;
        }

        $next = $tokens->getNextMeaningfulToken($index);
        $nextToken = $tokens[$next];

        if ($nextToken->isGivenKind(T_NS_SEPARATOR)) {
            return false;
        }

        if ($nextToken->isGivenKind([T_DOUBLE_COLON, T_ELLIPSIS, CT::T_TYPE_ALTERNATION, T_VARIABLE])) {
            return true;
        }

        $prev = $tokens->getPrevMeaningfulToken($index);

        while ($tokens[$prev]->isGivenKind([CT::T_NAMESPACE_OPERATOR, T_NS_SEPARATOR, T_STRING])) {
            $prev = $tokens->getPrevMeaningfulToken($prev);
        }

        $prevToken = $tokens[$prev];

        if ($prevToken->isGivenKind([T_EXTENDS, T_INSTANCEOF, T_INSTEADOF, T_IMPLEMENTS, T_NEW, CT::T_NULLABLE_TYPE, CT::T_TYPE_ALTERNATION, CT::T_TYPE_COLON, CT::T_USE_TRAIT])) {
            return true;
        }

        // `Foo & $bar` could be:
        //   - function reference parameter: function baz(Foo & $bar) {}
        //   - bit operator: $x = Foo & $bar;
        if ($nextToken->equals('&') && $tokens[$tokens->getNextMeaningfulToken($next)]->isGivenKind(T_VARIABLE)) {
            $checkIndex = $tokens->getPrevTokenOfKind($prev + 1, [';', '{', '}', [T_FUNCTION], [T_OPEN_TAG], [T_OPEN_TAG_WITH_ECHO]]);

            return $tokens[$checkIndex]->isGivenKind(T_FUNCTION);
        }

        if (!$prevToken->equals(',')) {
            return false;
        }

        do {
            $prev = $tokens->getPrevMeaningfulToken($prev);
        } while ($tokens[$prev]->equalsAny([',', [T_NS_SEPARATOR], [T_STRING], [CT::T_NAMESPACE_OPERATOR]]));

        return $tokens[$prev]->isGivenKind([T_IMPLEMENTS, CT::T_USE_TRAIT]);
    }
}
