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

namespace PhpCsFixer\Fixer\FunctionNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\TypeAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class FunctionTypehintSpaceFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Ensure single space between function\'s argument and its typehint.',
            [
                new CodeSample("<?php\nfunction sample(array\$a)\n{}\n"),
                new CodeSample("<?php\nfunction sample(array  \$a)\n{}\n"),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        if (\PHP_VERSION_ID >= 70400 && $tokens->isTokenKindFound(T_FN)) {
            return true;
        }

        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $functionsAnalyzer = new FunctionsAnalyzer();

        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            $token = $tokens[$index];

            if (
                !$token->isGivenKind(T_FUNCTION)
                && (\PHP_VERSION_ID < 70400 || !$token->isGivenKind(T_FN))
            ) {
                continue;
            }

            $arguments = $functionsAnalyzer->getFunctionArguments($tokens, $index);

            foreach (array_reverse($arguments) as $argument) {
                $type = $argument->getTypeAnalysis();

                if (!$type instanceof TypeAnalysis) {
                    continue;
                }

                $whitespaceTokenIndex = $type->getEndIndex() + 1;

                if ($tokens[$whitespaceTokenIndex]->equals([T_WHITESPACE])) {
                    if (' ' === $tokens[$whitespaceTokenIndex]->getContent()) {
                        continue;
                    }

                    $tokens->clearAt($whitespaceTokenIndex);
                }

                $tokens->insertAt($whitespaceTokenIndex, new Token([T_WHITESPACE, ' ']));
            }
        }
    }
}
