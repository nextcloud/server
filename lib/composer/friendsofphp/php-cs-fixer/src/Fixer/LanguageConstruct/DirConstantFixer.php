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

namespace PhpCsFixer\Fixer\LanguageConstruct;

use PhpCsFixer\AbstractFunctionReferenceFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Vladimir Reznichenko <kalessil@gmail.com>
 */
final class DirConstantFixer extends AbstractFunctionReferenceFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Replaces `dirname(__FILE__)` expression with equivalent `__DIR__` constant.',
            [new CodeSample("<?php\n\$a = dirname(__FILE__);\n")],
            null,
            'Risky when the function `dirname` is overridden.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_FILE);
    }

    /**
     * {@inheritdoc}
     *
     * Must run before CombineNestedDirnameFixer.
     */
    public function getPriority()
    {
        return 4;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $currIndex = 0;
        while (null !== $currIndex) {
            $boundaries = $this->find('dirname', $tokens, $currIndex, $tokens->count() - 1);
            if (null === $boundaries) {
                return;
            }

            list($functionNameIndex, $openParenthesis, $closeParenthesis) = $boundaries;

            // analysing cursor shift, so nested expressions kept processed
            $currIndex = $openParenthesis;

            // ensure __FILE__ is in between (...)

            $fileCandidateRightIndex = $tokens->getPrevMeaningfulToken($closeParenthesis);
            $trailingCommaIndex = null;
            if ($tokens[$fileCandidateRightIndex]->equals(',')) {
                $trailingCommaIndex = $fileCandidateRightIndex;
                $fileCandidateRightIndex = $tokens->getPrevMeaningfulToken($fileCandidateRightIndex);
            }

            $fileCandidateRight = $tokens[$fileCandidateRightIndex];
            if (!$fileCandidateRight->isGivenKind(T_FILE)) {
                continue;
            }

            $fileCandidateLeftIndex = $tokens->getNextMeaningfulToken($openParenthesis);
            $fileCandidateLeft = $tokens[$fileCandidateLeftIndex];

            if (!$fileCandidateLeft->isGivenKind(T_FILE)) {
                continue;
            }

            // get rid of root namespace when it used
            $namespaceCandidateIndex = $tokens->getPrevMeaningfulToken($functionNameIndex);
            $namespaceCandidate = $tokens[$namespaceCandidateIndex];
            if ($namespaceCandidate->isGivenKind(T_NS_SEPARATOR)) {
                $tokens->removeTrailingWhitespace($namespaceCandidateIndex);
                $tokens->clearAt($namespaceCandidateIndex);
            }

            if (null !== $trailingCommaIndex) {
                if (!$tokens[$tokens->getNextNonWhitespace($trailingCommaIndex)]->isComment()) {
                    $tokens->removeTrailingWhitespace($trailingCommaIndex);
                }

                $tokens->clearTokenAndMergeSurroundingWhitespace($trailingCommaIndex);
            }

            // closing parenthesis removed with leading spaces
            if (!$tokens[$tokens->getNextNonWhitespace($closeParenthesis)]->isComment()) {
                $tokens->removeLeadingWhitespace($closeParenthesis);
            }

            $tokens->clearTokenAndMergeSurroundingWhitespace($closeParenthesis);

            // opening parenthesis removed with trailing and leading spaces
            if (!$tokens[$tokens->getNextNonWhitespace($openParenthesis)]->isComment()) {
                $tokens->removeLeadingWhitespace($openParenthesis);
            }

            $tokens->removeTrailingWhitespace($openParenthesis);
            $tokens->clearTokenAndMergeSurroundingWhitespace($openParenthesis);

            // replace constant and remove function name
            $tokens[$fileCandidateLeftIndex] = new Token([T_DIR, '__DIR__']);
            $tokens->clearTokenAndMergeSurroundingWhitespace($functionNameIndex);
        }
    }
}
