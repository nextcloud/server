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

namespace PhpCsFixer\Fixer\Whitespace;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class ArrayIndentationFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Each element of an array must be indented exactly once.',
            [
                new CodeSample("<?php\n\$foo = [\n   'bar' => [\n    'baz' => true,\n  ],\n];\n"),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN]);
    }

    /**
     * {@inheritdoc}
     *
     * Must run before AlignMultilineCommentFixer, BinaryOperatorSpacesFixer.
     * Must run after BracesFixer, MethodChainingIndentationFixer.
     */
    public function getPriority()
    {
        return -30;
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($this->findArrays($tokens) as $array) {
            $indentLevel = 1;
            $scopes = [[
                'opening_braces' => $array['start_braces']['opening'],
                'unindented' => false,
            ]];
            $currentScope = 0;

            $arrayIndent = $this->getLineIndentation($tokens, $array['start']);
            $previousLineInitialIndent = $arrayIndent;
            $previousLineNewIndent = $arrayIndent;

            foreach ($array['braces'] as $index => $braces) {
                $currentIndentLevel = $indentLevel;
                if (
                    $braces['starts_with_closing']
                    && !$scopes[$currentScope]['unindented']
                    && !$this->isClosingLineWithMeaningfulContent($tokens, $index)
                ) {
                    --$currentIndentLevel;
                }

                $token = $tokens[$index];
                if ($this->newlineIsInArrayScope($tokens, $index, $array)) {
                    $content = Preg::replace(
                        '/(\R+)\h*$/',
                        '$1'.$arrayIndent.str_repeat($this->whitespacesConfig->getIndent(), $currentIndentLevel),
                        $token->getContent()
                    );

                    $previousLineInitialIndent = $this->extractIndent($token->getContent());
                    $previousLineNewIndent = $this->extractIndent($content);
                } else {
                    $content = Preg::replace(
                        '/(\R)'.preg_quote($previousLineInitialIndent, '/').'(\h*)$/',
                        '$1'.$previousLineNewIndent.'$2',
                        $token->getContent()
                    );
                }

                $closingBraces = $braces['closing'];
                while ($closingBraces-- > 0) {
                    if (!$scopes[$currentScope]['unindented']) {
                        --$indentLevel;
                        $scopes[$currentScope]['unindented'] = true;
                    }

                    if (0 === --$scopes[$currentScope]['opening_braces']) {
                        array_pop($scopes);
                        --$currentScope;
                    }
                }

                if ($braces['opening'] > 0) {
                    $scopes[] = [
                        'opening_braces' => $braces['opening'],
                        'unindented' => false,
                    ];
                    ++$indentLevel;
                    ++$currentScope;
                }

                $tokens[$index] = new Token([T_WHITESPACE, $content]);
            }
        }
    }

    private function findArrays(Tokens $tokens)
    {
        $arrays = [];

        foreach ($this->findArrayTokenRanges($tokens, 0, \count($tokens) - 1) as $arrayTokenRanges) {
            $array = [
                'start' => $arrayTokenRanges[0][0],
                'end' => $arrayTokenRanges[\count($arrayTokenRanges) - 1][1],
                'token_ranges' => $arrayTokenRanges,
            ];

            $array['start_braces'] = $this->getLineSignificantBraces($tokens, $array['start'] - 1, $array);
            $array['braces'] = $this->computeArrayLineSignificantBraces($tokens, $array);

            $arrays[] = $array;
        }

        return $arrays;
    }

    private function findArrayTokenRanges(Tokens $tokens, $from, $to)
    {
        $arrayTokenRanges = [];
        $currentArray = null;
        $valueSinceIndex = null;

        for ($index = $from; $index <= $to; ++$index) {
            $token = $tokens[$index];

            if ($token->isGivenKind([T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN])) {
                $arrayStartIndex = $index;

                if ($token->isGivenKind(T_ARRAY)) {
                    $index = $tokens->getNextTokenOfKind($index, ['(']);
                }

                $endIndex = $tokens->findBlockEnd(
                    $tokens[$index]->equals('(') ? Tokens::BLOCK_TYPE_PARENTHESIS_BRACE : Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE,
                    $index
                );

                if (null === $currentArray) {
                    $currentArray = [
                        'start' => $index,
                        'end' => $endIndex,
                        'ignored_tokens_ranges' => [],
                    ];
                } else {
                    if (null === $valueSinceIndex) {
                        $valueSinceIndex = $arrayStartIndex;
                    }

                    $index = $endIndex;
                }

                continue;
            }

            if (null === $currentArray || $token->isWhitespace() || $token->isComment()) {
                continue;
            }

            if ($currentArray['end'] === $index) {
                if (null !== $valueSinceIndex) {
                    $currentArray['ignored_tokens_ranges'][] = [$valueSinceIndex, $tokens->getPrevMeaningfulToken($index)];
                    $valueSinceIndex = null;
                }

                $rangeIndexes = [$currentArray['start']];
                foreach ($currentArray['ignored_tokens_ranges'] as list($start, $end)) {
                    $rangeIndexes[] = $start - 1;
                    $rangeIndexes[] = $end + 1;
                }
                $rangeIndexes[] = $currentArray['end'];

                $arrayTokenRanges[] = array_chunk($rangeIndexes, 2);

                foreach ($currentArray['ignored_tokens_ranges'] as list($start, $end)) {
                    foreach ($this->findArrayTokenRanges($tokens, $start, $end) as $nestedArray) {
                        $arrayTokenRanges[] = $nestedArray;
                    }
                }

                $currentArray = null;

                continue;
            }

            if (null === $valueSinceIndex) {
                $valueSinceIndex = $index;
            }

            if (
                ($token->equals('(') && !$tokens[$tokens->getPrevMeaningfulToken($index)]->isGivenKind(T_ARRAY))
                || $token->equals('{')
            ) {
                $index = $tokens->findBlockEnd(
                    $token->equals('{') ? Tokens::BLOCK_TYPE_CURLY_BRACE : Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
                    $index
                );
            }

            if ($token->equals(',')) {
                $currentArray['ignored_tokens_ranges'][] = [$valueSinceIndex, $tokens->getPrevMeaningfulToken($index)];
                $valueSinceIndex = null;
            }
        }

        return $arrayTokenRanges;
    }

    private function computeArrayLineSignificantBraces(Tokens $tokens, array $array)
    {
        $braces = [];

        for ($index = $array['start']; $index <= $array['end']; ++$index) {
            if (!$this->isNewLineToken($tokens, $index)) {
                continue;
            }

            $braces[$index] = $this->getLineSignificantBraces($tokens, $index, $array);
        }

        return $braces;
    }

    private function getLineSignificantBraces(Tokens $tokens, $index, array $array)
    {
        $deltas = [];

        for (++$index; $index <= $array['end']; ++$index) {
            if ($this->isNewLineToken($tokens, $index)) {
                break;
            }

            if (!$this->indexIsInArrayTokenRanges($index, $array)) {
                continue;
            }

            $token = $tokens[$index];
            if ($token->equals('(') && !$tokens[$tokens->getPrevMeaningfulToken($index)]->isGivenKind(T_ARRAY)) {
                continue;
            }

            if ($token->equals(')')) {
                $openBraceIndex = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);
                if (!$tokens[$tokens->getPrevMeaningfulToken($openBraceIndex)]->isGivenKind(T_ARRAY)) {
                    continue;
                }
            }

            if ($token->equalsAny(['(', [CT::T_ARRAY_SQUARE_BRACE_OPEN]])) {
                $deltas[] = 1;

                continue;
            }

            if ($token->equalsAny([')', [CT::T_ARRAY_SQUARE_BRACE_CLOSE]])) {
                $deltas[] = -1;
            }
        }

        $braces = [
            'opening' => 0,
            'closing' => 0,
            'starts_with_closing' => -1 === reset($deltas),
        ];

        foreach ($deltas as $delta) {
            if (1 === $delta) {
                ++$braces['opening'];
            } elseif ($braces['opening'] > 0) {
                --$braces['opening'];
            } else {
                ++$braces['closing'];
            }
        }

        return $braces;
    }

    private function isClosingLineWithMeaningfulContent(Tokens $tokens, $newLineIndex)
    {
        $nextMeaningfulIndex = $tokens->getNextMeaningfulToken($newLineIndex);

        return !$tokens[$nextMeaningfulIndex]->equalsAny([')', [CT::T_ARRAY_SQUARE_BRACE_CLOSE]]);
    }

    private function getLineIndentation(Tokens $tokens, $index)
    {
        $newlineTokenIndex = $this->getPreviousNewlineTokenIndex($tokens, $index);

        if (null === $newlineTokenIndex) {
            return '';
        }

        return $this->extractIndent($this->computeNewLineContent($tokens, $newlineTokenIndex));
    }

    private function extractIndent($content)
    {
        if (Preg::match('/\R(\h*)[^\r\n]*$/D', $content, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function getPreviousNewlineTokenIndex(Tokens $tokens, $index)
    {
        while ($index > 0) {
            $index = $tokens->getPrevTokenOfKind($index, [[T_WHITESPACE], [T_INLINE_HTML]]);

            if (null === $index) {
                break;
            }

            if ($this->isNewLineToken($tokens, $index)) {
                return $index;
            }
        }

        return null;
    }

    private function newlineIsInArrayScope(Tokens $tokens, $index, array $array)
    {
        if ($tokens[$tokens->getPrevMeaningfulToken($index)]->equalsAny(['.', '?', ':'])) {
            return false;
        }

        $nextToken = $tokens[$tokens->getNextMeaningfulToken($index)];
        if ($nextToken->isGivenKind(T_OBJECT_OPERATOR) || $nextToken->equalsAny(['.', '?', ':'])) {
            return false;
        }

        return $this->indexIsInArrayTokenRanges($index, $array);
    }

    private function indexIsInArrayTokenRanges($index, array $array)
    {
        foreach ($array['token_ranges'] as list($start, $end)) {
            if ($index < $start) {
                return false;
            }

            if ($index <= $end) {
                return true;
            }
        }

        return false;
    }

    private function isNewLineToken(Tokens $tokens, $index)
    {
        if (!$tokens[$index]->equalsAny([[T_WHITESPACE], [T_INLINE_HTML]])) {
            return false;
        }

        return (bool) Preg::match('/\R/', $this->computeNewLineContent($tokens, $index));
    }

    private function computeNewLineContent(Tokens $tokens, $index)
    {
        $content = $tokens[$index]->getContent();

        if (0 !== $index && $tokens[$index - 1]->equalsAny([[T_OPEN_TAG], [T_CLOSE_TAG]])) {
            $content = Preg::replace('/\S/', '', $tokens[$index - 1]->getContent()).$content;
        }

        return $content;
    }
}
