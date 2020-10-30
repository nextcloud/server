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

namespace PhpCsFixer\Fixer\PhpUnit;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class PhpUnitDedicateAssertInternalTypeFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * @var array
     */
    private $typeToDedicatedAssertMap = [
        'array' => 'assertIsArray',
        'boolean' => 'assertIsBool',
        'bool' => 'assertIsBool',
        'double' => 'assertIsFloat',
        'float' => 'assertIsFloat',
        'integer' => 'assertIsInt',
        'int' => 'assertIsInt',
        'null' => 'assertNull',
        'numeric' => 'assertIsNumeric',
        'object' => 'assertIsObject',
        'real' => 'assertIsFloat',
        'resource' => 'assertIsResource',
        'string' => 'assertIsString',
        'scalar' => 'assertIsScalar',
        'callable' => 'assertIsCallable',
        'iterable' => 'assertIsIterable',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'PHPUnit assertions like `assertIsArray` should be used over `assertInternalType`.',
            [
                new CodeSample(
                    '<?php
final class MyTest extends \PHPUnit\Framework\TestCase
{
    public function testMe()
    {
        $this->assertInternalType("array", $var);
        $this->assertInternalType("boolean", $var);
    }
}
'
                ),
            ],
            null,
            'Risky when PHPUnit methods are overridden or when project has PHPUnit incompatibilities.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAllTokenKindsFound([T_CLASS, T_FUNCTION]);
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * Must run after PhpUnitDedicateAssertFixer.
     */
    public function getPriority()
    {
        return -16;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            $this->updateAssertInternalTypeMethods($tokens, $indexes[0], $indexes[1]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('target', 'Target version of PHPUnit.'))
                ->setAllowedTypes(['string'])
                ->setAllowedValues([PhpUnitTargetVersion::VERSION_7_5, PhpUnitTargetVersion::VERSION_NEWEST])
                ->setDefault(PhpUnitTargetVersion::VERSION_NEWEST)
                ->getOption(),
        ]);
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    private function updateAssertInternalTypeMethods(Tokens $tokens, $startIndex, $endIndex)
    {
        $anonymousClassIndexes = [];
        $tokenAnalyzer = new TokensAnalyzer($tokens);
        for ($index = $startIndex; $index < $endIndex; ++$index) {
            if (!$tokens[$index]->isClassy() || !$tokenAnalyzer->isAnonymousClass($index)) {
                continue;
            }

            $openingBraceIndex = $tokens->getNextTokenOfKind($index, ['{']);
            $closingBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openingBraceIndex);

            $anonymousClassIndexes[$closingBraceIndex] = $openingBraceIndex;
        }

        for ($index = $endIndex - 1; $index > $startIndex; --$index) {
            if (isset($anonymousClassIndexes[$index])) {
                $index = $anonymousClassIndexes[$index];

                continue;
            }

            if (!$tokens[$index]->isGivenKind(T_STRING)) {
                continue;
            }

            $functionName = strtolower($tokens[$index]->getContent());
            if ('assertinternaltype' !== $functionName && 'assertnotinternaltype' !== $functionName) {
                continue;
            }

            $bracketTokenIndex = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$bracketTokenIndex]->equals('(')) {
                continue;
            }

            $expectedTypeTokenIndex = $tokens->getNextMeaningfulToken($bracketTokenIndex);
            $expectedTypeToken = $tokens[$expectedTypeTokenIndex];
            if (!$expectedTypeToken->equals([T_CONSTANT_ENCAPSED_STRING])) {
                continue;
            }

            $expectedType = trim($expectedTypeToken->getContent(), '\'"');
            if (!isset($this->typeToDedicatedAssertMap[$expectedType])) {
                continue;
            }

            $commaTokenIndex = $tokens->getNextMeaningfulToken($expectedTypeTokenIndex);
            if (!$tokens[$commaTokenIndex]->equals(',')) {
                continue;
            }

            $newAssertion = $this->typeToDedicatedAssertMap[$expectedType];
            if ('assertnotinternaltype' === $functionName) {
                $newAssertion = str_replace('Is', 'IsNot', $newAssertion);
                $newAssertion = str_replace('Null', 'NotNull', $newAssertion);
            }

            $nextMeaningfulTokenIndex = $tokens->getNextMeaningfulToken($commaTokenIndex);

            $tokens->overrideRange($index, $nextMeaningfulTokenIndex - 1, [
                new Token([T_STRING, $newAssertion]),
                new Token('('),
            ]);
        }
    }
}
