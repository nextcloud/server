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
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpUnitTestClassRequiresCoversFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Adds a default `@coversNothing` annotation to PHPUnit test classes that have no `@covers*` annotation.',
            [
                new CodeSample(
                    '<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testSomeTest()
    {
        $this->assertSame(a(), b());
    }
}
'
                ),
            ]
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
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();

        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens) as $indexes) {
            $this->addRequiresCover($tokens, $indexes[0]);
        }
    }

    private function addRequiresCover(Tokens $tokens, $startIndex)
    {
        $classIndex = $tokens->getPrevTokenOfKind($startIndex, [[T_CLASS]]);
        $prevIndex = $tokens->getPrevMeaningfulToken($classIndex);

        // don't add `@covers` annotation for abstract base classes
        if ($tokens[$prevIndex]->isGivenKind(T_ABSTRACT)) {
            return;
        }

        $index = $tokens[$prevIndex]->isGivenKind(T_FINAL) ? $prevIndex : $classIndex;

        $indent = $tokens[$index - 1]->isGivenKind(T_WHITESPACE)
            ? Preg::replace('/^.*\R*/', '', $tokens[$index - 1]->getContent())
            : '';

        $prevIndex = $tokens->getPrevNonWhitespace($index);

        if ($tokens[$prevIndex]->isGivenKind(T_DOC_COMMENT)) {
            $docIndex = $prevIndex;
            $docContent = $tokens[$docIndex]->getContent();

            // ignore one-line phpdocs like `/** foo */`, as there is no place to put new annotations
            if (false === strpos($docContent, "\n")) {
                return;
            }

            $doc = new DocBlock($docContent);

            // skip if already has annotation
            if (!empty($doc->getAnnotationsOfType([
                'covers',
                'coversDefaultClass',
                'coversNothing',
            ]))) {
                return;
            }
        } else {
            $docIndex = $index;
            $tokens->insertAt($docIndex, [
                new Token([T_DOC_COMMENT, sprintf('/**%s%s */', $this->whitespacesConfig->getLineEnding(), $indent)]),
                new Token([T_WHITESPACE, sprintf('%s%s', $this->whitespacesConfig->getLineEnding(), $indent)]),
            ]);

            if (!$tokens[$docIndex - 1]->isGivenKind(T_WHITESPACE)) {
                $extraNewLines = $this->whitespacesConfig->getLineEnding();

                if (!$tokens[$docIndex - 1]->isGivenKind(T_OPEN_TAG)) {
                    $extraNewLines .= $this->whitespacesConfig->getLineEnding();
                }

                $tokens->insertAt($docIndex, [
                    new Token([T_WHITESPACE, $extraNewLines.$indent]),
                ]);
                ++$docIndex;
            }

            $doc = new DocBlock($tokens[$docIndex]->getContent());
        }

        $lines = $doc->getLines();
        array_splice(
            $lines,
            \count($lines) - 1,
            0,
            [
                new Line(sprintf(
                    '%s * @coversNothing%s',
                    $indent,
                    $this->whitespacesConfig->getLineEnding()
                )),
            ]
        );

        $tokens[$docIndex] = new Token([T_DOC_COMMENT, implode('', $lines)]);
    }
}
