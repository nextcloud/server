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
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @author Gregor Harlan <gharlan@web.de>
 */
final class UnaryOperatorSpacesFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Unary operators should be placed adjacent to their operands.',
            [new CodeSample("<?php\n\$sample ++;\n-- \$sample;\n\$sample = ! ! \$a;\n\$sample = ~  \$c;\nfunction & foo(){}\n")]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before NotOperatorWithSpaceFixer, NotOperatorWithSuccessorSpaceFixer.
     */
    public function getPriority()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            if ($tokensAnalyzer->isUnarySuccessorOperator($index)) {
                if (!$tokens[$tokens->getPrevNonWhitespace($index)]->isComment()) {
                    $tokens->removeLeadingWhitespace($index);
                }

                continue;
            }

            if ($tokensAnalyzer->isUnaryPredecessorOperator($index)) {
                $tokens->removeTrailingWhitespace($index);

                continue;
            }
        }
    }
}
