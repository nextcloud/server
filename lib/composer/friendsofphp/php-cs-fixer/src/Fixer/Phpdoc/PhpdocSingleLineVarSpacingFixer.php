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

namespace PhpCsFixer\Fixer\Phpdoc;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Fixer for part of rule defined in PSR5 ¶7.22.
 *
 * @author SpacePossum
 */
final class PhpdocSingleLineVarSpacingFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Single line `@var` PHPDoc should have proper spacing.',
            [new CodeSample("<?php /**@var   MyClass   \$a   */\n\$a = test();\n")]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before PhpdocAlignFixer.
     * Must run after CommentToPhpdocFixer, PhpdocIndentFixer, PhpdocNoAliasTagFixer, PhpdocScalarFixer, PhpdocToCommentFixer, PhpdocTypesFixer.
     */
    public function getPriority()
    {
        return -10;
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
        /** @var Token $token */
        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind(T_DOC_COMMENT)) {
                $tokens[$index] = new Token([T_DOC_COMMENT, $this->fixTokenContent($token->getContent())]);

                continue;
            }

            if (!$token->isGivenKind(T_COMMENT)) {
                continue;
            }

            $content = $token->getContent();
            $fixedContent = $this->fixTokenContent($content);
            if ($content !== $fixedContent) {
                $tokens[$index] = new Token([T_DOC_COMMENT, $fixedContent]);
            }
        }
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function fixTokenContent($content)
    {
        return Preg::replaceCallback(
            '#^/\*\*\h*@var\h+(\S+)\h*(\$\S+)?\h*([^\n]*)\*/$#',
            static function (array $matches) {
                $content = '/** @var';
                for ($i = 1, $m = \count($matches); $i < $m; ++$i) {
                    if ('' !== $matches[$i]) {
                        $content .= ' '.$matches[$i];
                    }
                }

                $content = rtrim($content);

                return $content.' */';
            },
            $content
        );
    }
}
