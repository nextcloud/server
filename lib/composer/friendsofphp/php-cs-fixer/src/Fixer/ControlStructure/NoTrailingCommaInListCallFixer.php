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

namespace PhpCsFixer\Fixer\ControlStructure;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class NoTrailingCommaInListCallFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Remove trailing commas in list function calls.',
            [new CodeSample("<?php\nlist(\$a, \$b,) = foo();\n")]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_LIST);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_LIST)) {
                continue;
            }

            $openIndex = $tokens->getNextMeaningfulToken($index);
            $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openIndex);
            $markIndex = null;
            $prevIndex = $tokens->getPrevNonWhitespace($closeIndex);

            while ($tokens[$prevIndex]->equals(',')) {
                $markIndex = $prevIndex;
                $prevIndex = $tokens->getPrevNonWhitespace($prevIndex);
            }

            if (null !== $markIndex) {
                $tokens->clearRange(
                    $tokens->getPrevNonWhitespace($markIndex) + 1,
                    $closeIndex - 1
                );
            }
        }
    }
}
