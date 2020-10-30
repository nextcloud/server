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

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class TernaryToNullCoalescingFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Use `null` coalescing operator `??` where possible. Requires PHP >= 7.0.',
            [
                new VersionSpecificCodeSample(
                    "<?php\n\$sample = isset(\$a) ? \$a : \$b;\n",
                    new VersionSpecification(70000)
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return \PHP_VERSION_ID >= 70000 && $tokens->isTokenKindFound(T_ISSET);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $issetIndexes = array_keys($tokens->findGivenKind(T_ISSET));
        while ($issetIndex = array_pop($issetIndexes)) {
            $this->fixIsset($tokens, $issetIndex);
        }
    }

    /**
     * @param int $index of `T_ISSET` token
     */
    private function fixIsset(Tokens $tokens, $index)
    {
        $prevTokenIndex = $tokens->getPrevMeaningfulToken($index);
        if ($this->isHigherPrecedenceAssociativityOperator($tokens[$prevTokenIndex])) {
            return;
        }

        $startBraceIndex = $tokens->getNextTokenOfKind($index, ['(']);
        $endBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startBraceIndex);

        $ternaryQuestionMarkIndex = $tokens->getNextMeaningfulToken($endBraceIndex);
        if (!$tokens[$ternaryQuestionMarkIndex]->equals('?')) {
            return; // we are not in a ternary operator
        }

        // search what is inside the isset()
        $issetTokens = $this->getMeaningfulSequence($tokens, $startBraceIndex, $endBraceIndex);
        if ($this->hasChangingContent($issetTokens)) {
            return; // some weird stuff inside the isset
        }

        // search what is inside the middle argument of ternary operator
        $ternaryColonIndex = $tokens->getNextTokenOfKind($ternaryQuestionMarkIndex, [':']);
        $ternaryFirstOperandTokens = $this->getMeaningfulSequence($tokens, $ternaryQuestionMarkIndex, $ternaryColonIndex);

        if ($issetTokens->generateCode() !== $ternaryFirstOperandTokens->generateCode()) {
            return; // regardless of non-meaningful tokens, the operands are different
        }

        $ternaryFirstOperandIndex = $tokens->getNextMeaningfulToken($ternaryQuestionMarkIndex);

        // preserve comments and spaces
        $comments = [];
        $commentStarted = false;
        for ($loopIndex = $index; $loopIndex < $ternaryFirstOperandIndex; ++$loopIndex) {
            if ($tokens[$loopIndex]->isComment()) {
                $comments[] = $tokens[$loopIndex];
                $commentStarted = true;
            } elseif ($commentStarted) {
                if ($tokens[$loopIndex]->isWhitespace()) {
                    $comments[] = $tokens[$loopIndex];
                }

                $commentStarted = false;
            }
        }

        $tokens[$ternaryColonIndex] = new Token([T_COALESCE, '??']);
        $tokens->overrideRange($index, $ternaryFirstOperandIndex - 1, $comments);
    }

    /**
     * Get the sequence of meaningful tokens and returns a new Tokens instance.
     *
     * @param int $start start index
     * @param int $end   end index
     *
     * @return Tokens
     */
    private function getMeaningfulSequence(Tokens $tokens, $start, $end)
    {
        $sequence = [];
        $index = $start;
        while ($index < $end) {
            $index = $tokens->getNextMeaningfulToken($index);
            if ($index >= $end || null === $index) {
                break;
            }

            $sequence[] = $tokens[$index];
        }

        return Tokens::fromArray($sequence);
    }

    /**
     * Check if the requested token is an operator computed
     * before the ternary operator along with the `isset()`.
     *
     * @return bool
     */
    private function isHigherPrecedenceAssociativityOperator(Token $token)
    {
        static $operatorsPerId = [
            T_ARRAY_CAST => true,
            T_BOOLEAN_AND => true,
            T_BOOLEAN_OR => true,
            T_BOOL_CAST => true,
            T_COALESCE => true,
            T_DEC => true,
            T_DOUBLE_CAST => true,
            T_INC => true,
            T_INT_CAST => true,
            T_IS_EQUAL => true,
            T_IS_GREATER_OR_EQUAL => true,
            T_IS_IDENTICAL => true,
            T_IS_NOT_EQUAL => true,
            T_IS_NOT_IDENTICAL => true,
            T_IS_SMALLER_OR_EQUAL => true,
            T_OBJECT_CAST => true,
            T_POW => true,
            T_SL => true,
            T_SPACESHIP => true,
            T_SR => true,
            T_STRING_CAST => true,
            T_UNSET_CAST => true,
        ];

        static $operatorsPerContent = [
            '!',
            '%',
            '&',
            '*',
            '+',
            '-',
            '/',
            ':',
            '^',
            '|',
            '~',
        ];

        return isset($operatorsPerId[$token->getId()]) || $token->equalsAny($operatorsPerContent);
    }

    /**
     * Check if the `isset()` content may change if called multiple times.
     *
     * @param Tokens $tokens The original token list
     *
     * @return bool
     */
    private function hasChangingContent(Tokens $tokens)
    {
        static $operatorsPerId = [
            T_DEC,
            T_INC,
            T_STRING,
            T_YIELD,
            T_YIELD_FROM,
        ];

        foreach ($tokens as $token) {
            if ($token->isGivenKind($operatorsPerId) || $token->equals('(')) {
                return true;
            }
        }

        return false;
    }
}
