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
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\ArgumentAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author HypeMC
 */
final class NullableTypeDeclarationForDefaultNullValueFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Adds or removes `?` before type declarations for parameters with a default `null` value.',
            [
                new VersionSpecificCodeSample(
                    "<?php\nfunction sample(string \$str = null)\n{}\n",
                    new VersionSpecification(70100)
                ),
                new VersionSpecificCodeSample(
                    "<?php\nfunction sample(?string \$str = null)\n{}\n",
                    new VersionSpecification(70100),
                    ['use_nullable_type_declaration' => false]
                ),
            ],
            'Rule is applied only in a PHP 7.1+ environment.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        if (\PHP_VERSION_ID < 70100) {
            return false;
        }

        if (!$tokens->isTokenKindFound(T_VARIABLE)) {
            return false;
        }

        if (\PHP_VERSION_ID >= 70400 && $tokens->isTokenKindFound(T_FN)) {
            return true;
        }

        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    /**
     * {@inheritdoc}
     *
     * Must run before NoUnreachableDefaultArgumentValueFixer.
     */
    public function getPriority()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('use_nullable_type_declaration', 'Whether to add or remove `?` before type declarations for parameters with a default `null` value.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $functionsAnalyzer = new FunctionsAnalyzer();

        $tokenKinds = [T_FUNCTION];
        if (\PHP_VERSION_ID >= 70400) {
            $tokenKinds[] = T_FN;
        }

        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind($tokenKinds)) {
                continue;
            }

            $arguments = $functionsAnalyzer->getFunctionArguments($tokens, $index);

            $this->fixFunctionParameters($tokens, $arguments);
        }
    }

    /**
     * @param ArgumentAnalysis[] $arguments
     */
    private function fixFunctionParameters(Tokens $tokens, array $arguments)
    {
        foreach (array_reverse($arguments) as $argumentInfo) {
            // If the parameter doesn't have a type declaration or a default value null we can continue
            if (
                !$argumentInfo->hasTypeAnalysis()
                || !$argumentInfo->hasDefault()
                || 'null' !== strtolower($argumentInfo->getDefault())
            ) {
                continue;
            }

            $argumentTypeInfo = $argumentInfo->getTypeAnalysis();
            if (true === $this->configuration['use_nullable_type_declaration']) {
                if (!$argumentTypeInfo->isNullable()) {
                    $tokens->insertAt($argumentTypeInfo->getStartIndex(), new Token([CT::T_NULLABLE_TYPE, '?']));
                }
            } else {
                if ($argumentTypeInfo->isNullable()) {
                    $tokens->removeTrailingWhitespace($argumentTypeInfo->getStartIndex());
                    $tokens->clearTokenAndMergeSurroundingWhitespace($argumentTypeInfo->getStartIndex());
                }
            }
        }
    }
}
