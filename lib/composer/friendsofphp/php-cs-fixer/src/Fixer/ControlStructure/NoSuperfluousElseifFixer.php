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

use PhpCsFixer\AbstractNoUselessElseFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NoSuperfluousElseifFixer extends AbstractNoUselessElseFixer
{
    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_ELSE, T_ELSEIF]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Replaces superfluous `elseif` with `if`.',
            [
                new CodeSample("<?php\nif (\$a) {\n    return 1;\n} elseif (\$b) {\n    return 2;\n}\n"),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after NoAlternativeSyntaxFixer.
     */
    public function getPriority()
    {
        return parent::getPriority();
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if ($this->isElseif($tokens, $index) && $this->isSuperfluousElse($tokens, $index)) {
                $this->convertElseifToIf($tokens, $index);
            }
        }
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function isElseif(Tokens $tokens, $index)
    {
        if ($tokens[$index]->isGivenKind(T_ELSEIF)) {
            return true;
        }

        return $tokens[$index]->isGivenKind(T_ELSE) && $tokens[$tokens->getNextMeaningfulToken($index)]->isGivenKind(T_IF);
    }

    /**
     * @param int $index
     */
    private function convertElseifToIf(Tokens $tokens, $index)
    {
        if ($tokens[$index]->isGivenKind(T_ELSE)) {
            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
        } else {
            $tokens[$index] = new Token([T_IF, 'if']);
        }

        $whitespace = '';
        for ($previous = $index - 1; $previous > 0; --$previous) {
            $token = $tokens[$previous];
            if ($token->isWhitespace() && Preg::match('/(\R\N*)$/', $token->getContent(), $matches)) {
                $whitespace = $matches[1];

                break;
            }
        }

        if ('' === $whitespace) {
            return;
        }

        $previousToken = $tokens[$index - 1];
        if (!$previousToken->isWhitespace()) {
            $tokens->insertAt($index, new Token([T_WHITESPACE, $whitespace]));
        } elseif (!Preg::match('/\R/', $previousToken->getContent())) {
            $tokens[$index - 1] = new Token([T_WHITESPACE, $whitespace]);
        }
    }
}
