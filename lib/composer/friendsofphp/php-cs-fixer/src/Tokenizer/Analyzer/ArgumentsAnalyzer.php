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

use PhpCsFixer\Tokenizer\Analyzer\Analysis\ArgumentAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\TypeAnalysis;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Vladimir Reznichenko <kalessil@gmail.com>
 *
 * @internal
 */
final class ArgumentsAnalyzer
{
    /**
     * Count amount of parameters in a function/method reference.
     *
     * @param int $openParenthesis
     * @param int $closeParenthesis
     *
     * @return int
     */
    public function countArguments(Tokens $tokens, $openParenthesis, $closeParenthesis)
    {
        return \count($this->getArguments($tokens, $openParenthesis, $closeParenthesis));
    }

    /**
     * Returns start and end token indexes of arguments.
     *
     * Returns an array with each key being the first token of an
     * argument and the value the last. Including non-function tokens
     * such as comments and white space tokens, but without the separation
     * tokens like '(', ',' and ')'.
     *
     * @param int $openParenthesis
     * @param int $closeParenthesis
     *
     * @return array<int, int>
     */
    public function getArguments(Tokens $tokens, $openParenthesis, $closeParenthesis)
    {
        $arguments = [];
        $firstSensibleToken = $tokens->getNextMeaningfulToken($openParenthesis);
        if ($tokens[$firstSensibleToken]->equals(')')) {
            return $arguments;
        }

        $paramContentIndex = $openParenthesis + 1;
        $argumentsStart = $paramContentIndex;
        for (; $paramContentIndex < $closeParenthesis; ++$paramContentIndex) {
            $token = $tokens[$paramContentIndex];

            // skip nested (), [], {} constructs
            $blockDefinitionProbe = Tokens::detectBlockType($token);

            if (null !== $blockDefinitionProbe && true === $blockDefinitionProbe['isStart']) {
                $paramContentIndex = $tokens->findBlockEnd($blockDefinitionProbe['type'], $paramContentIndex);

                continue;
            }

            // if comma matched, increase arguments counter
            if ($token->equals(',')) {
                if ($tokens->getNextMeaningfulToken($paramContentIndex) === $closeParenthesis) {
                    break; // trailing ',' in function call (PHP 7.3)
                }

                $arguments[$argumentsStart] = $paramContentIndex - 1;
                $argumentsStart = $paramContentIndex + 1;
            }
        }

        $arguments[$argumentsStart] = $paramContentIndex - 1;

        return $arguments;
    }

    /**
     * @param int $argumentStart
     * @param int $argumentEnd
     *
     * @return ArgumentAnalysis
     */
    public function getArgumentInfo(Tokens $tokens, $argumentStart, $argumentEnd)
    {
        $info = [
            'default' => null,
            'name' => null,
            'name_index' => null,
            'type' => null,
            'type_index_start' => null,
            'type_index_end' => null,
        ];

        $sawName = false;
        for ($index = $argumentStart; $index <= $argumentEnd; ++$index) {
            $token = $tokens[$index];
            if ($token->isComment() || $token->isWhitespace() || $token->isGivenKind(T_ELLIPSIS) || $token->equals('&')) {
                continue;
            }
            if ($token->isGivenKind(T_VARIABLE)) {
                $sawName = true;
                $info['name_index'] = $index;
                $info['name'] = $token->getContent();

                continue;
            }
            if ($token->equals('=')) {
                continue;
            }
            if ($sawName) {
                $info['default'] .= $token->getContent();
            } else {
                $info['type_index_start'] = ($info['type_index_start'] > 0) ? $info['type_index_start'] : $index;
                $info['type_index_end'] = $index;
                $info['type'] .= $token->getContent();
            }
        }

        return new ArgumentAnalysis(
            $info['name'],
            $info['name_index'],
            $info['default'],
            $info['type'] ? new TypeAnalysis($info['type'], $info['type_index_start'], $info['type_index_end']) : null
        );
    }
}
