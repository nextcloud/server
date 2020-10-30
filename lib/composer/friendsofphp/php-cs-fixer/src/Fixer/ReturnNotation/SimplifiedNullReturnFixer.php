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

namespace PhpCsFixer\Fixer\ReturnNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Graham Campbell <graham@alt-three.com>
 */
final class SimplifiedNullReturnFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'A return statement wishing to return `void` should not return `null`.',
            [
                new CodeSample("<?php return null;\n"),
                new VersionSpecificCodeSample(
                    <<<'EOT'
<?php
function foo() { return null; }
function bar(): int { return null; }
function baz(): ?int { return null; }
function xyz(): void { return null; }

EOT
                    ,
                    new VersionSpecification(70100)
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before NoUselessReturnFixer.
     */
    public function getPriority()
    {
        return -17;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_RETURN);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_RETURN)) {
                continue;
            }

            if ($this->needFixing($tokens, $index)) {
                $this->clear($tokens, $index);
            }
        }
    }

    /**
     * Clear the return statement located at a given index.
     *
     * @param int $index
     */
    private function clear(Tokens $tokens, $index)
    {
        while (!$tokens[++$index]->equals(';')) {
            if ($this->shouldClearToken($tokens, $index)) {
                $tokens->clearAt($index);
            }
        }
    }

    /**
     * Does the return statement located at a given index need fixing?
     *
     * @param int $index
     *
     * @return bool
     */
    private function needFixing(Tokens $tokens, $index)
    {
        if ($this->isStrictOrNullableReturnTypeFunction($tokens, $index)) {
            return false;
        }

        $content = '';
        while (!$tokens[$index]->equals(';')) {
            $index = $tokens->getNextMeaningfulToken($index);
            $content .= $tokens[$index]->getContent();
        }

        $content = ltrim($content, '(');
        $content = rtrim($content, ');');

        return 'null' === strtolower($content);
    }

    /**
     * Is the return within a function with a non-void or nullable return type?
     *
     * @param int $returnIndex Current return token index
     *
     * @return bool
     */
    private function isStrictOrNullableReturnTypeFunction(Tokens $tokens, $returnIndex)
    {
        $functionIndex = $returnIndex;
        do {
            $functionIndex = $tokens->getPrevTokenOfKind($functionIndex, [[T_FUNCTION]]);
            if (null === $functionIndex) {
                return false;
            }
            $openingCurlyBraceIndex = $tokens->getNextTokenOfKind($functionIndex, ['{']);
            $closingCurlyBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openingCurlyBraceIndex);
        } while ($closingCurlyBraceIndex < $returnIndex);

        $possibleVoidIndex = $tokens->getPrevMeaningfulToken($openingCurlyBraceIndex);
        $isStrictReturnType = $tokens[$possibleVoidIndex]->isGivenKind(T_STRING) && 'void' !== $tokens[$possibleVoidIndex]->getContent();

        $nullableTypeIndex = $tokens->getNextTokenOfKind($functionIndex, [[CT::T_NULLABLE_TYPE]]);
        $isNullableReturnType = null !== $nullableTypeIndex && $nullableTypeIndex < $openingCurlyBraceIndex;

        return $isStrictReturnType || $isNullableReturnType;
    }

    /**
     * Should we clear the specific token?
     *
     * If the token is a comment, or is whitespace that is immediately before a
     * comment, then we'll leave it alone.
     *
     * @param int $index
     *
     * @return bool
     */
    private function shouldClearToken(Tokens $tokens, $index)
    {
        $token = $tokens[$index];

        return !$token->isComment() && !($token->isWhitespace() && $tokens[$index + 1]->isComment());
    }
}
