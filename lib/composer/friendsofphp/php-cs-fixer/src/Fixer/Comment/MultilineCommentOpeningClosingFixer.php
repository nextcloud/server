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

namespace PhpCsFixer\Fixer\Comment;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class MultilineCommentOpeningClosingFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'DocBlocks must start with two asterisks, multiline comments must start with a single asterisk, after the opening slash. Both must end with a single asterisk before the closing slash.',
            [
                new CodeSample(
                    <<<'EOT'
<?php

/******
 * Multiline comment with arbitrary asterisks count
 ******/

/**\
 * Multiline comment that seems a DocBlock
 */

/**
 * DocBlock with arbitrary asterisk count at the end
 **/

EOT
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            $originalContent = $token->getContent();

            if (
                !$token->isGivenKind(T_DOC_COMMENT)
                && !($token->isGivenKind(T_COMMENT) && 0 === strpos($originalContent, '/*'))
            ) {
                continue;
            }

            $newContent = $originalContent;

            // Fix opening
            if ($token->isGivenKind(T_COMMENT)) {
                $newContent = Preg::replace('/^\\/\\*{2,}(?!\\/)/', '/*', $newContent);
            }

            // Fix closing
            $newContent = Preg::replace('/(?<!\\/)\\*{2,}\\/$/', '*/', $newContent);

            if ($newContent !== $originalContent) {
                $tokens[$index] = new Token([$token->getId(), $newContent]);
            }
        }
    }
}
