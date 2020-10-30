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
use PhpCsFixer\Tokenizer\Analyzer\CommentsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Ceeram <ceeram@cakephp.org>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpdocToCommentFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    /**
     * {@inheritdoc}
     *
     * Must run before GeneralPhpdocAnnotationRemoveFixer, NoBlankLinesAfterPhpdocFixer, NoEmptyCommentFixer, NoEmptyPhpdocFixer, NoSuperfluousPhpdocTagsFixer, PhpdocAddMissingParamAnnotationFixer, PhpdocAlignFixer, PhpdocAlignFixer, PhpdocAnnotationWithoutDotFixer, PhpdocIndentFixer, PhpdocInlineTagFixer, PhpdocLineSpanFixer, PhpdocNoAccessFixer, PhpdocNoAliasTagFixer, PhpdocNoEmptyReturnFixer, PhpdocNoPackageFixer, PhpdocNoUselessInheritdocFixer, PhpdocNoUselessInheritdocFixer, PhpdocOrderFixer, PhpdocReturnSelfReferenceFixer, PhpdocSeparationFixer, PhpdocSingleLineVarSpacingFixer, PhpdocSummaryFixer, PhpdocToParamTypeFixer, PhpdocToReturnTypeFixer, PhpdocTrimConsecutiveBlankLineSeparationFixer, PhpdocTrimFixer, PhpdocTypesOrderFixer, PhpdocVarAnnotationCorrectOrderFixer, PhpdocVarWithoutNameFixer.
     * Must run after CommentToPhpdocFixer.
     */
    public function getPriority()
    {
        /*
         * Should be run before all other docblock fixers so that these fixers
         * don't touch doc comments which are meant to be converted to regular
         * comments.
         */
        return 25;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Docblocks should only be used on structural elements.',
            [
                new CodeSample(
                    '<?php
$first = true;// needed because by default first docblock is never fixed.

/** This should not be a docblock */
foreach($connections as $key => $sqlite) {
    $sqlite->open($path);
}
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $commentsAnalyzer = new CommentsAnalyzer();

        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            if ($commentsAnalyzer->isHeaderComment($tokens, $index)) {
                continue;
            }

            if ($commentsAnalyzer->isBeforeStructuralElement($tokens, $index)) {
                continue;
            }

            $tokens[$index] = new Token([T_COMMENT, '/*'.ltrim($token->getContent(), '/*')]);
        }
    }
}
