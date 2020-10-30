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
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Michał Adamski <michal.adamski@gmail.com>
 * @author Kuba Werłos <werlos@gmail.com>
 */
final class PhpUnitMockShortWillReturnFixer extends AbstractFixer
{
    /**
     * @internal
     */
    const RETURN_METHODS_MAP = [
        'returnargument' => 'willReturnArgument',
        'returncallback' => 'willReturnCallback',
        'returnself' => 'willReturnSelf',
        'returnvalue' => 'willReturn',
        'returnvaluemap' => 'willReturnMap',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Usage of PHPUnit\'s mock e.g. `->will($this->returnValue(..))` must be replaced by its shorter equivalent such as `->willReturn(...)`.',
            [
                new CodeSample('<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testSomeTest()
    {
        $someMock = $this->createMock(Some::class);
        $someMock->method("some")->will($this->returnSelf());
        $someMock->method("some")->will($this->returnValue("example"));
        $someMock->method("some")->will($this->returnArgument(2));
        $someMock->method("some")->will($this->returnCallback("str_rot13"));
        $someMock->method("some")->will($this->returnValueMap(["a","b","c"]));
    }
}
'),
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
        return $tokens->isAllTokenKindsFound([T_CLASS, T_OBJECT_OPERATOR, T_STRING]);
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
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            $this->fixWillReturn($tokens, $indexes[0], $indexes[1]);
        }
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    private function fixWillReturn(Tokens $tokens, $startIndex, $endIndex)
    {
        for ($index = $startIndex; $index < $endIndex; ++$index) {
            if (!$tokens[$index]->isGivenKind(T_OBJECT_OPERATOR)) {
                continue;
            }

            $functionToReplaceIndex = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$functionToReplaceIndex]->equals([T_STRING, 'will'], false)) {
                continue;
            }

            $functionToReplaceOpeningBraceIndex = $tokens->getNextMeaningfulToken($functionToReplaceIndex);
            if (!$tokens[$functionToReplaceOpeningBraceIndex]->equals('(')) {
                continue;
            }

            $classReferenceIndex = $tokens->getNextMeaningfulToken($functionToReplaceOpeningBraceIndex);
            $objectOperatorIndex = $tokens->getNextMeaningfulToken($classReferenceIndex);
            if (
                !($tokens[$classReferenceIndex]->equals([T_VARIABLE, '$this'], false) && $tokens[$objectOperatorIndex]->equals([T_OBJECT_OPERATOR, '->']))
                && !($tokens[$classReferenceIndex]->equals([T_STRING, 'self'], false) && $tokens[$objectOperatorIndex]->equals([T_DOUBLE_COLON, '::']))
                && !($tokens[$classReferenceIndex]->equals([T_STATIC, 'static'], false) && $tokens[$objectOperatorIndex]->equals([T_DOUBLE_COLON, '::']))
            ) {
                continue;
            }

            $functionToRemoveIndex = $tokens->getNextMeaningfulToken($objectOperatorIndex);
            if (!$tokens[$functionToRemoveIndex]->isGivenKind(T_STRING) || !\array_key_exists(strtolower($tokens[$functionToRemoveIndex]->getContent()), self::RETURN_METHODS_MAP)) {
                continue;
            }

            $openingBraceIndex = $tokens->getNextMeaningfulToken($functionToRemoveIndex);
            if (!$tokens[$openingBraceIndex]->equals('(')) {
                continue;
            }

            $closingBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openingBraceIndex);

            $tokens[$functionToReplaceIndex] = new Token([T_STRING, self::RETURN_METHODS_MAP[strtolower($tokens[$functionToRemoveIndex]->getContent())]]);
            $tokens->clearTokenAndMergeSurroundingWhitespace($classReferenceIndex);
            $tokens->clearTokenAndMergeSurroundingWhitespace($objectOperatorIndex);
            $tokens->clearTokenAndMergeSurroundingWhitespace($functionToRemoveIndex);
            $tokens->clearTokenAndMergeSurroundingWhitespace($openingBraceIndex);
            $tokens->clearTokenAndMergeSurroundingWhitespace($closingBraceIndex);
        }
    }
}
