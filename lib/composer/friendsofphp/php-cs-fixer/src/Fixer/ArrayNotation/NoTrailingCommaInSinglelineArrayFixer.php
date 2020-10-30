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

namespace PhpCsFixer\Fixer\ArrayNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class NoTrailingCommaInSinglelineArrayFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'PHP single-line arrays should not have trailing comma.',
            [new CodeSample("<?php\n\$a = array('sample',  );\n")]
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
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        for ($index = 0, $c = $tokens->count(); $index < $c; ++$index) {
            if ($tokensAnalyzer->isArray($index)) {
                $this->fixArray($tokens, $index);
            }
        }
    }

    /**
     * @param int $index
     */
    private function fixArray(Tokens $tokens, $index)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        if ($tokensAnalyzer->isArrayMultiLine($index)) {
            return;
        }

        $startIndex = $index;

        if ($tokens[$startIndex]->isGivenKind(T_ARRAY)) {
            $startIndex = $tokens->getNextTokenOfKind($startIndex, ['(']);
            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startIndex);
        } else {
            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $startIndex);
        }

        $beforeEndIndex = $tokens->getPrevMeaningfulToken($endIndex);
        $beforeEndToken = $tokens[$beforeEndIndex];

        if ($beforeEndToken->equals(',')) {
            $tokens->removeTrailingWhitespace($beforeEndIndex);
            $tokens->clearAt($beforeEndIndex);
        }
    }
}
