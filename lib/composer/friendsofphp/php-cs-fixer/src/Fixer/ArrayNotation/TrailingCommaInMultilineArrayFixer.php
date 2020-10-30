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

namespace PhpCsFixer\Fixer\ArrayNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerConfiguration\InvalidOptionsForEnvException;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use Symfony\Component\OptionsResolver\Options;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class TrailingCommaInMultilineArrayFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'PHP multi-line arrays should have a trailing comma.',
            [
                new CodeSample("<?php\narray(\n    1,\n    2\n);\n"),
                new VersionSpecificCodeSample(
                    <<<'SAMPLE'
<?php
    $x = [
        'foo',
        <<<EOD
            bar
            EOD
    ];

SAMPLE
                    ,
                    new VersionSpecification(70300),
                    ['after_heredoc' => true]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after NoMultilineWhitespaceAroundDoubleArrowFixer.
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
        return $tokens->isAnyTokenKindsFound([T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);

        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            if ($tokensAnalyzer->isArray($index) && $tokensAnalyzer->isArrayMultiLine($index)) {
                $this->fixArray($tokens, $index);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('after_heredoc', 'Whether a trailing comma should also be placed after heredoc end.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(false)
                ->setNormalizer(static function (Options $options, $value) {
                    if (\PHP_VERSION_ID < 70300 && $value) {
                        throw new InvalidOptionsForEnvException('"after_heredoc" option can only be enabled with PHP 7.3+.');
                    }

                    return $value;
                })
                ->getOption(),
        ]);
    }

    /**
     * @param int $index
     */
    private function fixArray(Tokens $tokens, $index)
    {
        $startIndex = $index;

        if ($tokens[$startIndex]->isGivenKind(T_ARRAY)) {
            $startIndex = $tokens->getNextTokenOfKind($startIndex, ['(']);
            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startIndex);
        } else {
            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $startIndex);
        }

        $beforeEndIndex = $tokens->getPrevMeaningfulToken($endIndex);
        $beforeEndToken = $tokens[$beforeEndIndex];

        // if there is some item between braces then add `,` after it
        if (
            $startIndex !== $beforeEndIndex && !$beforeEndToken->equals(',') &&
            ($this->configuration['after_heredoc'] || !$beforeEndToken->isGivenKind(T_END_HEREDOC))
        ) {
            $tokens->insertAt($beforeEndIndex + 1, new Token(','));

            $endToken = $tokens[$endIndex];

            if (!$endToken->isComment() && !$endToken->isWhitespace()) {
                $tokens->ensureWhitespaceAtIndex($endIndex, 1, ' ');
            }
        }
    }
}
