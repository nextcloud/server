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
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class EscapeImplicitBackslashesFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        $codeSample = <<<'EOF'
<?php

$singleQuoted = 'String with \" and My\Prefix\\';

$doubleQuoted = "Interpret my \n but not my \a";

$hereDoc = <<<HEREDOC
Interpret my \100 but not my \999
HEREDOC;

EOF;

        return new FixerDefinition(
            'Escape implicit backslashes in strings and heredocs to ease the understanding of which are special chars interpreted by PHP and which not.',
            [
                new CodeSample($codeSample),
                new CodeSample(
                    $codeSample,
                    ['single_quoted' => true]
                ),
                new CodeSample(
                    $codeSample,
                    ['double_quoted' => false]
                ),
                new CodeSample(
                    $codeSample,
                    ['heredoc_syntax' => false]
                ),
            ],
            'In PHP double-quoted strings and heredocs some chars like `n`, `$` or `u` have special meanings if preceded by a backslash '
            .'(and some are special only if followed by other special chars), while a backslash preceding other chars are interpreted like a plain '
            .'backslash. The precise list of those special chars is hard to remember and to identify quickly: this fixer escapes backslashes '
            ."that do not start a special interpretation with the char after them.\n"
            .'It is possible to fix also single-quoted strings: in this case there is no special chars apart from single-quote and backslash '
            .'itself, so the fixer simply ensure that all backslashes are escaped. Both single and double backslashes are allowed in single-quoted '
            .'strings, so the purpose in this context is mainly to have a uniformed way to have them written all over the codebase.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_ENCAPSED_AND_WHITESPACE, T_CONSTANT_ENCAPSED_STRING]);
    }

    /**
     * {@inheritdoc}
     *
     * Must run before HeredocToNowdocFixer, SingleQuoteFixer.
     * Must run after BacktickToShellExecFixer.
     */
    public function getPriority()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        static $singleQuotedRegex = '/(?<!\\\\)\\\\((?:\\\\\\\\)*)(?![\\\'\\\\])/';
        static $doubleQuotedRegex = '/(?<!\\\\)\\\\((?:\\\\\\\\)*)(?![efnrtv$"\\\\0-7]|x[0-9A-Fa-f]|u{)/';
        static $heredocSyntaxRegex = '/(?<!\\\\)\\\\((?:\\\\\\\\)*)(?![efnrtv$\\\\0-7]|x[0-9A-Fa-f]|u{)/';

        $doubleQuoteOpened = false;
        foreach ($tokens as $index => $token) {
            $content = $token->getContent();
            if ($token->equalsAny(['"', 'b"', 'B"'])) {
                $doubleQuoteOpened = !$doubleQuoteOpened;
            }
            if (!$token->isGivenKind([T_ENCAPSED_AND_WHITESPACE, T_CONSTANT_ENCAPSED_STRING]) || false === strpos($content, '\\')) {
                continue;
            }

            // Nowdoc syntax
            if ($token->isGivenKind(T_ENCAPSED_AND_WHITESPACE) && '\'' === substr(rtrim($tokens[$index - 1]->getContent()), -1)) {
                continue;
            }

            $firstTwoCharacters = strtolower(substr($content, 0, 2));
            $isSingleQuotedString = $token->isGivenKind(T_CONSTANT_ENCAPSED_STRING) && ('\'' === $content[0] || 'b\'' === $firstTwoCharacters);
            $isDoubleQuotedString =
                ($token->isGivenKind(T_CONSTANT_ENCAPSED_STRING) && ('"' === $content[0] || 'b"' === $firstTwoCharacters))
                || ($token->isGivenKind(T_ENCAPSED_AND_WHITESPACE) && $doubleQuoteOpened)
            ;
            $isHeredocSyntax = !$isSingleQuotedString && !$isDoubleQuotedString;
            if (
                (false === $this->configuration['single_quoted'] && $isSingleQuotedString)
                || (false === $this->configuration['double_quoted'] && $isDoubleQuotedString)
                || (false === $this->configuration['heredoc_syntax'] && $isHeredocSyntax)
            ) {
                continue;
            }

            $regex = $heredocSyntaxRegex;
            if ($isSingleQuotedString) {
                $regex = $singleQuotedRegex;
            } elseif ($isDoubleQuotedString) {
                $regex = $doubleQuotedRegex;
            }

            $newContent = Preg::replace($regex, '\\\\\\\\$1', $content);
            if ($newContent !== $content) {
                $tokens[$index] = new Token([$token->getId(), $newContent]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('single_quoted', 'Whether to fix single-quoted strings.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(false)
                ->getOption(),
            (new FixerOptionBuilder('double_quoted', 'Whether to fix double-quoted strings.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
            (new FixerOptionBuilder('heredoc_syntax', 'Whether to fix heredoc syntax.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
        ]);
    }
}
