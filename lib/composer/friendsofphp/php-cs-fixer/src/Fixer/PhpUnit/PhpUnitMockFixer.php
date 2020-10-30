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
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpUnitMockFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * @var bool
     */
    private $fixCreatePartialMock;

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Usages of `->getMock` and `->getMockWithoutInvokingTheOriginalConstructor` methods MUST be replaced by `->createMock` or `->createPartialMock` methods.',
            [
                new CodeSample(
                    '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testFoo()
    {
        $mock = $this->getMockWithoutInvokingTheOriginalConstructor("Foo");
        $mock1 = $this->getMock("Foo");
        $mock1 = $this->getMock("Bar", ["aaa"]);
        $mock1 = $this->getMock("Baz", ["aaa"], ["argument"]); // version with more than 2 params is not supported
    }
}
'
                ),
                new CodeSample(
                    '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testFoo()
    {
        $mock1 = $this->getMock("Foo");
        $mock1 = $this->getMock("Bar", ["aaa"]); // version with multiple params is not supported
    }
}
',
                    ['target' => PhpUnitTargetVersion::VERSION_5_4]
                ),
            ],
            null,
            'Risky when PHPUnit classes are overridden or not accessible, or when project has PHPUnit incompatibilities.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_CLASS);
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
     */
    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        $this->fixCreatePartialMock = PhpUnitTargetVersion::fulfills($this->configuration['target'], PhpUnitTargetVersion::VERSION_5_5);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();
        $argumentsAnalyzer = new ArgumentsAnalyzer();

        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            for ($index = $indexes[0]; $index < $indexes[1]; ++$index) {
                if (!$tokens[$index]->isGivenKind(T_OBJECT_OPERATOR)) {
                    continue;
                }

                $index = $tokens->getNextMeaningfulToken($index);

                if ($tokens[$index]->equals([T_STRING, 'getMockWithoutInvokingTheOriginalConstructor'], false)) {
                    $tokens[$index] = new Token([T_STRING, 'createMock']);
                } elseif ($tokens[$index]->equals([T_STRING, 'getMock'], false)) {
                    $openingParenthesis = $tokens->getNextMeaningfulToken($index);
                    $closingParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingParenthesis);

                    $argumentsCount = $argumentsAnalyzer->countArguments($tokens, $openingParenthesis, $closingParenthesis);

                    if (1 === $argumentsCount) {
                        $tokens[$index] = new Token([T_STRING, 'createMock']);
                    } elseif (2 === $argumentsCount && true === $this->fixCreatePartialMock) {
                        $tokens[$index] = new Token([T_STRING, 'createPartialMock']);
                    }
                }
            }
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
                ->setAllowedValues([PhpUnitTargetVersion::VERSION_5_4, PhpUnitTargetVersion::VERSION_5_5, PhpUnitTargetVersion::VERSION_NEWEST])
                ->setDefault(PhpUnitTargetVersion::VERSION_NEWEST)
                ->getOption(),
        ]);
    }
}
