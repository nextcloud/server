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
 * @author Gregor Harlan <gharlan@web.de>
 */
final class SingleQuoteFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        $codeSample = <<<'EOF'
<?php

$a = "sample";
$b = "sample with 'single-quotes'";

EOF;

        return new FixerDefinition(
            'Convert double quotes to single quotes for simple strings.',
            [
                new CodeSample($codeSample),
                new CodeSample(
                    $codeSample,
                    ['strings_containing_single_quote_chars' => true]
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
        return $tokens->isTokenKindFound(T_CONSTANT_ENCAPSED_STRING);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }

            $content = $token->getContent();
            $prefix = '';

            if ('b' === strtolower($content[0])) {
                $prefix = $content[0];
                $content = substr($content, 1);
            }

            if (
                '"' === $content[0] &&
                (true === $this->configuration['strings_containing_single_quote_chars'] || false === strpos($content, "'")) &&
                // regex: odd number of backslashes, not followed by double quote or dollar
                !Preg::match('/(?<!\\\\)(?:\\\\{2})*\\\\(?!["$\\\\])/', $content)
            ) {
                $content = substr($content, 1, -1);
                $content = str_replace(['\\"', '\\$', '\''], ['"', '$', '\\\''], $content);
                $tokens[$index] = new Token([T_CONSTANT_ENCAPSED_STRING, $prefix.'\''.$content.'\'']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('strings_containing_single_quote_chars', 'Whether to fix double-quoted strings that contains single-quotes.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(false)
                ->getOption(),
        ]);
    }
}
