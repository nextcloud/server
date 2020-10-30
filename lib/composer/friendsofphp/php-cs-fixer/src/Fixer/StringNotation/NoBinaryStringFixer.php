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

namespace PhpCsFixer\Fixer\StringNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author ntzm
 */
final class NoBinaryStringFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_CONSTANT_ENCAPSED_STRING, T_START_HEREDOC]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'There should not be a binary flag before strings.',
            [
                new CodeSample("<?php \$a = b'foo';\n"),
                new CodeSample("<?php \$a = b<<<EOT\nfoo\nEOT;\n"),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind([T_CONSTANT_ENCAPSED_STRING, T_START_HEREDOC])) {
                continue;
            }

            $content = $token->getContent();

            if ('b' === strtolower($content[0])) {
                $tokens[$index] = new Token([$token->getId(), substr($content, 1)]);
            }
        }
    }
}
