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
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Roland Franssen <franssen.roland@gmail.com>
 */
final class PhpUnitFqcnAnnotationFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'PHPUnit annotations should be a FQCNs including a root namespace.',
            [new CodeSample(
                '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     * @covers Project\NameSpace\Something
     * @coversDefaultClass Project\Default
     * @uses Project\Test\Util
     */
    public function testSomeTest()
    {
    }
}
'
            )]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before NoUnusedImportsFixer, PhpUnitOrderedCoversFixer.
     */
    public function getPriority()
    {
        return -9;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAllTokenKindsFound([T_CLASS, T_DOC_COMMENT]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();
        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            $startIndex = $indexes[0];
            $prevDocCommentIndex = $tokens->getPrevTokenOfKind($startIndex, [[T_DOC_COMMENT]]);
            if (null !== $prevDocCommentIndex) {
                $startIndex = $prevDocCommentIndex;
            }
            $this->fixPhpUnitClass($tokens, $startIndex, $indexes[1]);
        }
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    private function fixPhpUnitClass(Tokens $tokens, $startIndex, $endIndex)
    {
        for ($index = $startIndex; $index < $endIndex; ++$index) {
            if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                $tokens[$index] = new Token([T_DOC_COMMENT, Preg::replace(
                    '~^(\s*\*\s*@(?:expectedException|covers|coversDefaultClass|uses)\h+)(?!(?:self|static)::)(\w.*)$~m',
                    '$1\\\\$2',
                    $tokens[$index]->getContent()
                )]);
            }
        }
    }
}
