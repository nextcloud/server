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
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Gregor Harlan <gharlan@web.de>
 */
final class HeredocToNowdocFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Convert `heredoc` to `nowdoc` where possible.',
            [
                new CodeSample(
                    <<<'EOF'
<?php $a = <<<"TEST"
Foo
TEST;

EOF
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after EscapeImplicitBackslashesFixer.
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
        return $tokens->isTokenKindFound(T_START_HEREDOC);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_START_HEREDOC) || false !== strpos($token->getContent(), "'")) {
                continue;
            }

            if ($tokens[$index + 1]->isGivenKind(T_END_HEREDOC)) {
                $tokens[$index] = $this->convertToNowdoc($token);

                continue;
            }

            if (
                !$tokens[$index + 1]->isGivenKind(T_ENCAPSED_AND_WHITESPACE) ||
                !$tokens[$index + 2]->isGivenKind(T_END_HEREDOC)
            ) {
                continue;
            }

            $content = $tokens[$index + 1]->getContent();
            // regex: odd number of backslashes, not followed by dollar
            if (Preg::match('/(?<!\\\\)(?:\\\\{2})*\\\\(?![$\\\\])/', $content)) {
                continue;
            }

            $tokens[$index] = $this->convertToNowdoc($token);
            $content = str_replace(['\\\\', '\\$'], ['\\', '$'], $content);
            $tokens[$index + 1] = new Token([
                $tokens[$index + 1]->getId(),
                $content,
            ]);
        }
    }

    /**
     * Transforms the heredoc start token to nowdoc notation.
     *
     * @return Token
     */
    private function convertToNowdoc(Token $token)
    {
        return new Token([
            $token->getId(),
            Preg::replace('/^([Bb]?<<<)(\h*)"?([^\s"]+)"?/', '$1$2\'$3\'', $token->getContent()),
        ]);
    }
}
