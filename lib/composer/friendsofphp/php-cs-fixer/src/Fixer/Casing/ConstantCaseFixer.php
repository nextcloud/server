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

namespace PhpCsFixer\Fixer\Casing;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Fixer for constants case.
 *
 * @author Pol Dellaiera <pol.dellaiera@protonmail.com>
 */
final class ConstantCaseFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * Hold the function that will be used to convert the constants.
     *
     * @var callable
     */
    private $fixFunction;

    /**
     * {@inheritdoc}
     */
    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        if ('lower' === $this->configuration['case']) {
            $this->fixFunction = static function ($token) {
                return strtolower($token);
            };
        }

        if ('upper' === $this->configuration['case']) {
            $this->fixFunction = static function ($token) {
                return strtoupper($token);
            };
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'The PHP constants `true`, `false`, and `null` MUST be written using the correct casing.',
            [new CodeSample("<?php\n\$a = FALSE;\n\$b = True;\n\$c = nuLL;\n")]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('case', 'Whether to use the `upper` or `lower` case syntax.'))
                ->setAllowedValues(['upper', 'lower'])
                ->setDefault('lower')
                ->getOption(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $fixFunction = $this->fixFunction;

        foreach ($tokens as $index => $token) {
            if (!$token->isNativeConstant()) {
                continue;
            }

            if (
                $this->isNeighbourAccepted($tokens, $tokens->getPrevMeaningfulToken($index)) &&
                $this->isNeighbourAccepted($tokens, $tokens->getNextMeaningfulToken($index))
            ) {
                $tokens[$index] = new Token([$token->getId(), $fixFunction($token->getContent())]);
            }
        }
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function isNeighbourAccepted(Tokens $tokens, $index)
    {
        static $forbiddenTokens = [
            T_AS,
            T_CLASS,
            T_CONST,
            T_EXTENDS,
            T_IMPLEMENTS,
            T_INSTANCEOF,
            T_INSTEADOF,
            T_INTERFACE,
            T_NEW,
            T_NS_SEPARATOR,
            T_OBJECT_OPERATOR,
            T_PAAMAYIM_NEKUDOTAYIM,
            T_TRAIT,
            T_USE,
            CT::T_USE_TRAIT,
            CT::T_USE_LAMBDA,
        ];

        $token = $tokens[$index];

        if ($token->equalsAny(['{', '}'])) {
            return false;
        }

        return !$token->isGivenKind($forbiddenTokens);
    }
}
