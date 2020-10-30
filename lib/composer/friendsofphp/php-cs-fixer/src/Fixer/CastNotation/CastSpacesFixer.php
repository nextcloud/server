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

namespace PhpCsFixer\Fixer\CastNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class CastSpacesFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * @internal
     */
    const INSIDE_CAST_SPACE_REPLACE_MAP = [
        ' ' => '',
        "\t" => '',
        "\n" => '',
        "\r" => '',
        "\0" => '',
        "\x0B" => '',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'A single space or none should be between cast and variable.',
            [
                new CodeSample(
                    "<?php\n\$bar = ( string )  \$a;\n\$foo = (int)\$b;\n"
                ),
                new CodeSample(
                    "<?php\n\$bar = ( string )  \$a;\n\$foo = (int)\$b;\n",
                    ['space' => 'single']
                ),
                new CodeSample(
                    "<?php\n\$bar = ( string )  \$a;\n\$foo = (int) \$b;\n",
                    ['space' => 'none']
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after NoShortBoolCastFixer.
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
        return $tokens->isAnyTokenKindsFound(Token::getCastTokenKinds());
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isCast()) {
                continue;
            }

            $tokens[$index] = new Token([
                $token->getId(),
                strtr($token->getContent(), self::INSIDE_CAST_SPACE_REPLACE_MAP),
            ]);

            if ('single' === $this->configuration['space']) {
                // force single whitespace after cast token:
                if ($tokens[$index + 1]->isWhitespace(" \t")) {
                    // - if next token is whitespaces that contains only spaces and tabs - override next token with single space
                    $tokens[$index + 1] = new Token([T_WHITESPACE, ' ']);
                } elseif (!$tokens[$index + 1]->isWhitespace()) {
                    // - if next token is not whitespaces that contains spaces, tabs and new lines - append single space to current token
                    $tokens->insertAt($index + 1, new Token([T_WHITESPACE, ' ']));
                }

                continue;
            }

            // force no whitespace after cast token:
            if ($tokens[$index + 1]->isWhitespace()) {
                $tokens->clearAt($index + 1);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('space', 'spacing to apply between cast and variable.'))
                ->setAllowedValues(['none', 'single'])
                ->setDefault('single')
                ->getOption(),
        ]);
    }
}
