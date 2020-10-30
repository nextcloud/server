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
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Utils;

/**
 * @author Ceeram <ceeram@cakephp.org>
 * @author Graham Campbell <graham@alt-three.com>
 */
final class PhpdocIndentFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Docblocks should have the same indentation as the documented subject.',
            [new CodeSample('<?php
class DocBlocks
{
/**
 * Test constants
 */
    const INDENT = 1;
}
')]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before GeneralPhpdocAnnotationRemoveFixer, NoBlankLinesAfterPhpdocFixer, NoEmptyPhpdocFixer, NoSuperfluousPhpdocTagsFixer, PhpdocAddMissingParamAnnotationFixer, PhpdocAlignFixer, PhpdocAlignFixer, PhpdocAnnotationWithoutDotFixer, PhpdocInlineTagFixer, PhpdocLineSpanFixer, PhpdocNoAccessFixer, PhpdocNoAliasTagFixer, PhpdocNoEmptyReturnFixer, PhpdocNoPackageFixer, PhpdocNoUselessInheritdocFixer, PhpdocOrderFixer, PhpdocReturnSelfReferenceFixer, PhpdocSeparationFixer, PhpdocSingleLineVarSpacingFixer, PhpdocSummaryFixer, PhpdocToParamTypeFixer, PhpdocToReturnTypeFixer, PhpdocTrimConsecutiveBlankLineSeparationFixer, PhpdocTrimFixer, PhpdocTypesFixer, PhpdocTypesOrderFixer, PhpdocVarAnnotationCorrectOrderFixer, PhpdocVarWithoutNameFixer.
     * Must run after IndentationTypeFixer, PhpdocToCommentFixer.
     */
    public function getPriority()
    {
        return 20;
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

            $nextIndex = $tokens->getNextMeaningfulToken($index);

            // skip if there is no next token or if next token is block end `}`
            if (null === $nextIndex || $tokens[$nextIndex]->equals('}')) {
                continue;
            }

            $prevIndex = $index - 1;
            $prevToken = $tokens[$prevIndex];

            // ignore inline docblocks
            if (
                $prevToken->isGivenKind(T_OPEN_TAG)
                || ($prevToken->isWhitespace(" \t") && !$tokens[$index - 2]->isGivenKind(T_OPEN_TAG))
                || $prevToken->equalsAny([';', ',', '{', '('])
            ) {
                continue;
            }

            $indent = '';
            if ($tokens[$nextIndex - 1]->isWhitespace()) {
                $indent = Utils::calculateTrailingWhitespaceIndent($tokens[$nextIndex - 1]);
            }

            $newPrevContent = $this->fixWhitespaceBeforeDocblock($prevToken->getContent(), $indent);
            if ($newPrevContent) {
                if ($prevToken->isArray()) {
                    $tokens[$prevIndex] = new Token([$prevToken->getId(), $newPrevContent]);
                } else {
                    $tokens[$prevIndex] = new Token($newPrevContent);
                }
            } else {
                $tokens->clearAt($prevIndex);
            }

            $tokens[$index] = new Token([T_DOC_COMMENT, $this->fixDocBlock($token->getContent(), $indent)]);
        }
    }

    /**
     * Fix indentation of Docblock.
     *
     * @param string $content Docblock contents
     * @param string $indent  Indentation to apply
     *
     * @return string Dockblock contents including correct indentation
     */
    private function fixDocBlock($content, $indent)
    {
        return ltrim(Preg::replace('/^\h*\*/m', $indent.' *', $content));
    }

    /**
     * @param string $content Whitespace before Docblock
     * @param string $indent  Indentation of the documented subject
     *
     * @return string Whitespace including correct indentation for Dockblock after this whitespace
     */
    private function fixWhitespaceBeforeDocblock($content, $indent)
    {
        return rtrim($content, " \t").$indent;
    }
}
