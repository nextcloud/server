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

namespace PhpCsFixer\Fixer\CastNotation;

use PhpCsFixer\AbstractFunctionReferenceFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Vladimir Reznichenko <kalessil@gmail.com>
 */
final class ModernizeTypesCastingFixer extends AbstractFunctionReferenceFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Replaces `intval`, `floatval`, `doubleval`, `strval` and `boolval` function calls with according type casting operator.',
            [
                new CodeSample(
                    '<?php
    $a = intval($b);
    $a = floatval($b);
    $a = doubleval($b);
    $a = strval ($b);
    $a = boolval($b);
'
                ),
            ],
            null,
            'Risky if any of the functions `intval`, `floatval`, `doubleval`, `strval` or `boolval` are overridden.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        // replacement patterns
        static $replacement = [
            'intval' => [T_INT_CAST, '(int)'],
            'floatval' => [T_DOUBLE_CAST, '(float)'],
            'doubleval' => [T_DOUBLE_CAST, '(float)'],
            'strval' => [T_STRING_CAST, '(string)'],
            'boolval' => [T_BOOL_CAST, '(bool)'],
        ];

        $argumentsAnalyzer = new ArgumentsAnalyzer();

        foreach ($replacement as $functionIdentity => $newToken) {
            $currIndex = 0;
            while (null !== $currIndex) {
                // try getting function reference and translate boundaries for humans
                $boundaries = $this->find($functionIdentity, $tokens, $currIndex, $tokens->count() - 1);
                if (null === $boundaries) {
                    // next function search, as current one not found
                    continue 2;
                }

                list($functionName, $openParenthesis, $closeParenthesis) = $boundaries;

                // analysing cursor shift
                $currIndex = $openParenthesis;

                // indicator that the function is overridden
                if (1 !== $argumentsAnalyzer->countArguments($tokens, $openParenthesis, $closeParenthesis)) {
                    continue;
                }

                $paramContentEnd = $closeParenthesis;
                $commaCandidate = $tokens->getPrevMeaningfulToken($paramContentEnd);
                if ($tokens[$commaCandidate]->equals(',')) {
                    $tokens->removeTrailingWhitespace($commaCandidate);
                    $tokens->clearAt($commaCandidate);
                    $paramContentEnd = $commaCandidate;
                }

                // check if something complex passed as an argument and preserve parenthesises then
                $countParamTokens = 0;
                for ($paramContentIndex = $openParenthesis + 1; $paramContentIndex < $paramContentEnd; ++$paramContentIndex) {
                    //not a space, means some sensible token
                    if (!$tokens[$paramContentIndex]->isGivenKind(T_WHITESPACE)) {
                        ++$countParamTokens;
                    }
                }

                $preserveParenthesises = $countParamTokens > 1;

                $afterCloseParenthesisIndex = $tokens->getNextMeaningfulToken($closeParenthesis);
                $afterCloseParenthesisToken = $tokens[$afterCloseParenthesisIndex];
                $wrapInParenthesises = $afterCloseParenthesisToken->equalsAny(['[', '{']) || $afterCloseParenthesisToken->isGivenKind(T_POW);

                // analyse namespace specification (root one or none) and decide what to do
                $prevTokenIndex = $tokens->getPrevMeaningfulToken($functionName);
                if ($tokens[$prevTokenIndex]->isGivenKind(T_NS_SEPARATOR)) {
                    // get rid of root namespace when it used
                    $tokens->removeTrailingWhitespace($prevTokenIndex);
                    $tokens->clearAt($prevTokenIndex);
                }

                // perform transformation
                $replacementSequence = [
                    new Token($newToken),
                    new Token([T_WHITESPACE, ' ']),
                ];
                if ($wrapInParenthesises) {
                    array_unshift($replacementSequence, new Token('('));
                }

                if (!$preserveParenthesises) {
                    // closing parenthesis removed with leading spaces
                    $tokens->removeLeadingWhitespace($closeParenthesis);
                    $tokens->clearAt($closeParenthesis);

                    // opening parenthesis removed with trailing spaces
                    $tokens->removeLeadingWhitespace($openParenthesis);
                    $tokens->removeTrailingWhitespace($openParenthesis);
                    $tokens->clearAt($openParenthesis);
                } else {
                    // we'll need to provide a space after a casting operator
                    $tokens->removeTrailingWhitespace($functionName);
                }

                if ($wrapInParenthesises) {
                    $tokens->insertAt($closeParenthesis, new Token(')'));
                }

                $tokens->overrideRange($functionName, $functionName, $replacementSequence);

                // nested transformations support
                $currIndex = $functionName;
            }
        }
    }
}
