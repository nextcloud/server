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
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Indicator\PhpUnitTestCaseIndicator;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * @author Jefersson Nathan <malukenho.dev@gmail.com>
 */
final class PhpUnitSizeClassFixer extends AbstractFixer implements WhitespacesAwareFixerInterface, ConfigurationDefinitionFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'All PHPUnit test cases should have `@small`, `@medium` or `@large` annotation to enable run time limits.',
            [
                new CodeSample("<?php\nclass MyTest extends TestCase {}\n"),
                new CodeSample("<?php\nclass MyTest extends TestCase {}\n", ['group' => 'medium']),
            ],
            'The special groups [small, medium, large] provides a way to identify tests that are taking long to be executed.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAllTokenKindsFound([T_CLASS, T_STRING]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $phpUnitTestCaseIndicator = new PhpUnitTestCaseIndicator();

        foreach ($phpUnitTestCaseIndicator->findPhpUnitClasses($tokens, true) as $indexes) {
            $this->markClassSize($tokens, $indexes[0]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('group', 'Define a specific group to be used in case no group is already in use'))
                ->setAllowedValues(['small', 'medium', 'large'])
                ->setDefault('small')
                ->getOption(),
        ]);
    }

    /**
     * @param int $startIndex
     */
    private function markClassSize(Tokens $tokens, $startIndex)
    {
        $classIndex = $tokens->getPrevTokenOfKind($startIndex, [[T_CLASS]]);

        if ($this->isAbstractClass($tokens, $classIndex)) {
            return;
        }

        $docBlockIndex = $this->getDocBlockIndex($tokens, $classIndex);

        if ($this->hasDocBlock($tokens, $classIndex)) {
            $this->updateDocBlockIfNeeded($tokens, $docBlockIndex);

            return;
        }

        $this->createDocBlock($tokens, $docBlockIndex);
    }

    /**
     * @param int $i
     *
     * @return bool
     */
    private function isAbstractClass(Tokens $tokens, $i)
    {
        $typeIndex = $tokens->getPrevMeaningfulToken($i);

        return $tokens[$typeIndex]->isGivenKind(T_ABSTRACT);
    }

    private function createDocBlock(Tokens $tokens, $docBlockIndex)
    {
        $lineEnd = $this->whitespacesConfig->getLineEnding();
        $originalIndent = $this->detectIndent($tokens, $tokens->getNextNonWhitespace($docBlockIndex));
        $group = $this->configuration['group'];
        $toInsert = [
            new Token([T_DOC_COMMENT, '/**'.$lineEnd."{$originalIndent} * @".$group.$lineEnd."{$originalIndent} */"]),
            new Token([T_WHITESPACE, $lineEnd.$originalIndent]),
        ];
        $index = $tokens->getNextMeaningfulToken($docBlockIndex);
        $tokens->insertAt($index, $toInsert);
    }

    private function updateDocBlockIfNeeded(Tokens $tokens, $docBlockIndex)
    {
        $doc = new DocBlock($tokens[$docBlockIndex]->getContent());
        if (!empty($this->filterDocBlock($doc))) {
            return;
        }
        $doc = $this->makeDocBlockMultiLineIfNeeded($doc, $tokens, $docBlockIndex);
        $lines = $this->addSizeAnnotation($doc, $tokens, $docBlockIndex);
        $lines = implode('', $lines);

        $tokens[$docBlockIndex] = new Token([T_DOC_COMMENT, $lines]);
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function hasDocBlock(Tokens $tokens, $index)
    {
        $docBlockIndex = $this->getDocBlockIndex($tokens, $index);

        return $tokens[$docBlockIndex]->isGivenKind(T_DOC_COMMENT);
    }

    /**
     * @param int $index
     *
     * @return int
     */
    private function getDocBlockIndex(Tokens $tokens, $index)
    {
        do {
            $index = $tokens->getPrevNonWhitespace($index);
        } while ($tokens[$index]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE, T_FINAL, T_ABSTRACT, T_COMMENT]));

        return $index;
    }

    /**
     * @param int $index
     *
     * @return string
     */
    private function detectIndent(Tokens $tokens, $index)
    {
        if (!$tokens[$index - 1]->isWhitespace()) {
            return ''; // cannot detect indent
        }

        $explodedContent = explode($this->whitespacesConfig->getLineEnding(), $tokens[$index - 1]->getContent());

        return end($explodedContent);
    }

    /**
     * @param int $docBlockIndex
     *
     * @return Line[]
     */
    private function addSizeAnnotation(DocBlock $docBlock, Tokens $tokens, $docBlockIndex)
    {
        $lines = $docBlock->getLines();
        $originalIndent = $this->detectIndent($tokens, $docBlockIndex);
        $lineEnd = $this->whitespacesConfig->getLineEnding();
        $group = $this->configuration['group'];
        array_splice($lines, -1, 0, $originalIndent.' *'.$lineEnd.$originalIndent.' * @'.$group.$lineEnd);

        return $lines;
    }

    /**
     * @param int $docBlockIndex
     *
     * @return DocBlock
     */
    private function makeDocBlockMultiLineIfNeeded(DocBlock $doc, Tokens $tokens, $docBlockIndex)
    {
        $lines = $doc->getLines();
        if (1 === \count($lines) && empty($this->filterDocBlock($doc))) {
            $lines = $this->splitUpDocBlock($lines, $tokens, $docBlockIndex);

            return new DocBlock(implode('', $lines));
        }

        return $doc;
    }

    /**
     * Take a one line doc block, and turn it into a multi line doc block.
     *
     * @param Line[] $lines
     * @param int    $docBlockIndex
     *
     * @return Line[]
     */
    private function splitUpDocBlock($lines, Tokens $tokens, $docBlockIndex)
    {
        $lineContent = $this->getSingleLineDocBlockEntry($lines);
        $lineEnd = $this->whitespacesConfig->getLineEnding();
        $originalIndent = $this->detectIndent($tokens, $tokens->getNextNonWhitespace($docBlockIndex));

        return [
            new Line('/**'.$lineEnd),
            new Line($originalIndent.' * '.$lineContent.$lineEnd),
            new Line($originalIndent.' */'),
        ];
    }

    /**
     * @param Line|Line[]|string $line
     *
     * @return string
     */
    private function getSingleLineDocBlockEntry($line)
    {
        $line = $line[0];
        $line = str_replace('*/', '', $line);
        $line = trim($line);
        $line = str_split($line);
        $i = \count($line);
        do {
            --$i;
        } while ('*' !== $line[$i] && '*' !== $line[$i - 1] && '/' !== $line[$i - 2]);
        if (' ' === $line[$i]) {
            ++$i;
        }
        $line = \array_slice($line, $i);

        return implode('', $line);
    }

    /**
     * @return Annotation[][]
     */
    private function filterDocBlock(DocBlock $doc)
    {
        return array_filter([
            $doc->getAnnotationsOfType('small'),
            $doc->getAnnotationsOfType('large'),
            $doc->getAnnotationsOfType('medium'),
        ]);
    }
}
