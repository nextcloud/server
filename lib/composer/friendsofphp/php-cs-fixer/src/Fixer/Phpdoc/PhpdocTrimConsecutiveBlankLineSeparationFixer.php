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

namespace PhpCsFixer\Fixer\Phpdoc;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\DocBlock\ShortDescription;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Nobu Funaki <nobu.funaki@gmail.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpdocTrimConsecutiveBlankLineSeparationFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Removes extra blank lines after summary and after description in PHPDoc.',
            [
                new CodeSample(
                    '<?php
/**
 * Summary.
 *
 *
 * Description that contain 4 lines,
 *
 *
 * while 2 of them are blank!
 *
 *
 * @param string $foo
 *
 *
 * @dataProvider provideFixCases
 */
function fnc($foo) {}
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before PhpdocAlignFixer.
     * Must run after CommentToPhpdocFixer, PhpdocIndentFixer, PhpdocScalarFixer, PhpdocToCommentFixer, PhpdocTypesFixer.
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
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $doc = new DocBlock($token->getContent());
            $summaryEnd = (new ShortDescription($doc))->getEnd();

            if (null !== $summaryEnd) {
                $this->fixSummary($doc, $summaryEnd);
                $this->fixDescription($doc, $summaryEnd);
            }

            $this->fixAllTheRest($doc);

            $tokens[$index] = new Token([T_DOC_COMMENT, $doc->getContent()]);
        }
    }

    /**
     * @param int $summaryEnd
     */
    private function fixSummary(DocBlock $doc, $summaryEnd)
    {
        $nonBlankLineAfterSummary = $this->findNonBlankLine($doc, $summaryEnd);

        $this->removeExtraBlankLinesBetween($doc, $summaryEnd, $nonBlankLineAfterSummary);
    }

    /**
     * @param int $summaryEnd
     */
    private function fixDescription(DocBlock $doc, $summaryEnd)
    {
        $annotationStart = $this->findFirstAnnotationOrEnd($doc);

        // assuming the end of the Description appears before the first Annotation
        $descriptionEnd = $this->reverseFindLastUsefulContent($doc, $annotationStart);

        if (null === $descriptionEnd || $summaryEnd === $descriptionEnd) {
            return; // no Description
        }

        if ($annotationStart === \count($doc->getLines()) - 1) {
            return; // no content after Description
        }

        $this->removeExtraBlankLinesBetween($doc, $descriptionEnd, $annotationStart);
    }

    private function fixAllTheRest(DocBlock $doc)
    {
        $annotationStart = $this->findFirstAnnotationOrEnd($doc);
        $lastLine = $this->reverseFindLastUsefulContent($doc, \count($doc->getLines()) - 1);

        if (null !== $lastLine && $annotationStart !== $lastLine) {
            $this->removeExtraBlankLinesBetween($doc, $annotationStart, $lastLine);
        }
    }

    /**
     * @param int $from
     * @param int $to
     */
    private function removeExtraBlankLinesBetween(DocBlock $doc, $from, $to)
    {
        for ($index = $from + 1; $index < $to; ++$index) {
            $line = $doc->getLine($index);
            $next = $doc->getLine($index + 1);
            $this->removeExtraBlankLine($line, $next);
        }
    }

    private function removeExtraBlankLine(Line $current, Line $next)
    {
        if (!$current->isTheEnd() && !$current->containsUsefulContent()
            && !$next->isTheEnd() && !$next->containsUsefulContent()) {
            $current->remove();
        }
    }

    /**
     * @param int $after
     *
     * @return null|int
     */
    private function findNonBlankLine(DocBlock $doc, $after)
    {
        foreach ($doc->getLines() as $index => $line) {
            if ($index <= $after) {
                continue;
            }

            if ($line->containsATag() || $line->containsUsefulContent() || $line->isTheEnd()) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return int
     */
    private function findFirstAnnotationOrEnd(DocBlock $doc)
    {
        $index = null;
        foreach ($doc->getLines() as $index => $line) {
            if ($line->containsATag()) {
                return $index;
            }
        }

        return $index; // no Annotation, return the last line
    }

    /**
     * @param int $from
     *
     * @return null|int
     */
    private function reverseFindLastUsefulContent(DocBlock $doc, $from)
    {
        for ($index = $from - 1; $index >= 0; --$index) {
            if ($doc->getLine($index)->containsUsefulContent()) {
                return $index;
            }
        }

        return null;
    }
}
