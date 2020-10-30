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

namespace PhpCsFixer\Fixer\ReturnNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author SpacePossum
 */
final class ReturnAssignmentFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Local, dynamic and directly referenced variables should not be assigned and directly returned by a function or method.',
            [new CodeSample("<?php\nfunction a() {\n    \$a = 1;\n    return \$a;\n}\n")]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before BlankLineBeforeStatementFixer.
     * Must run after NoEmptyStatementFixer, NoUnneededCurlyBracesFixer.
     */
    public function getPriority()
    {
        return -15;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAllTokenKindsFound([T_FUNCTION, T_RETURN, T_VARIABLE]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $tokenCount = \count($tokens);

        for ($index = 1; $index < $tokenCount; ++$index) {
            if (!$tokens[$index]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $functionOpenIndex = $tokens->getNextTokenOfKind($index, ['{', ';']);
            if ($tokens[$functionOpenIndex]->equals(';')) { // abstract function
                $index = $functionOpenIndex - 1;

                continue;
            }

            $functionCloseIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $functionOpenIndex);
            $totalTokensAdded = 0;

            do {
                $tokensAdded = $this->fixFunction(
                    $tokens,
                    $index,
                    $functionOpenIndex,
                    $functionCloseIndex
                );

                $totalTokensAdded += $tokensAdded;
            } while ($tokensAdded > 0);

            $index = $functionCloseIndex + $totalTokensAdded;
            $tokenCount += $totalTokensAdded;
        }
    }

    /**
     * @param int $functionIndex      token index of T_FUNCTION
     * @param int $functionOpenIndex  token index of the opening brace token of the function
     * @param int $functionCloseIndex token index of the closing brace token of the function
     *
     * @return int >= 0 number of tokens inserted into the Tokens collection
     */
    private function fixFunction(Tokens $tokens, $functionIndex, $functionOpenIndex, $functionCloseIndex)
    {
        static $riskyKinds = [
            CT::T_DYNAMIC_VAR_BRACE_OPEN, // "$h = ${$g};" case
            T_EVAL,                       // "$c = eval('return $this;');" case
            T_GLOBAL,
            T_INCLUDE,                    // loading additional symbols we cannot analyze here
            T_INCLUDE_ONCE,               // "
            T_REQUIRE,                    // "
            T_REQUIRE_ONCE,               // "
            T_STATIC,
        ];

        $inserted = 0;
        $candidates = [];
        $isRisky = false;

        // go through the function declaration and check if references are passed
        // - check if it will be risky to fix return statements of this function
        for ($index = $functionIndex + 1; $index < $functionOpenIndex; ++$index) {
            if ($tokens[$index]->equals('&')) {
                $isRisky = true;

                break;
            }
        }

        // go through all the tokens of the body of the function:
        // - check if it will be risky to fix return statements of this function
        // - check nested functions; fix when found and update the upper limit + number of inserted token
        // - check for return statements that might be fixed (based on if fixing will be risky, which is only know after analyzing the whole function)

        for ($index = $functionOpenIndex + 1; $index < $functionCloseIndex; ++$index) {
            if ($tokens[$index]->isGivenKind(T_FUNCTION)) {
                $nestedFunctionOpenIndex = $tokens->getNextTokenOfKind($index, ['{', ';']);
                if ($tokens[$nestedFunctionOpenIndex]->equals(';')) { // abstract function
                    $index = $nestedFunctionOpenIndex - 1;

                    continue;
                }

                $nestedFunctionCloseIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $nestedFunctionOpenIndex);

                $tokensAdded = $this->fixFunction(
                    $tokens,
                    $index,
                    $nestedFunctionOpenIndex,
                    $nestedFunctionCloseIndex
                );

                $index = $nestedFunctionCloseIndex + $tokensAdded;
                $functionCloseIndex += $tokensAdded;
                $inserted += $tokensAdded;
            }

            if ($isRisky) {
                continue; // don't bother to look into anything else than nested functions as the current is risky already
            }

            if ($tokens[$index]->equals('&')) {
                $isRisky = true;

                continue;
            }

            if ($tokens[$index]->isGivenKind(T_RETURN)) {
                $candidates[] = $index;

                continue;
            }

            // test if there this is anything in the function body that might
            // change global state or indirect changes (like through references, eval, etc.)

            if ($tokens[$index]->isGivenKind($riskyKinds)) {
                $isRisky = true;

                continue;
            }

            if ($tokens[$index]->equals('$')) {
                $nextIndex = $tokens->getNextMeaningfulToken($index);
                if ($tokens[$nextIndex]->isGivenKind(T_VARIABLE)) {
                    $isRisky = true; // "$$a" case

                    continue;
                }
            }

            if ($this->isSuperGlobal($tokens[$index])) {
                $isRisky = true;

                continue;
            }
        }

        if ($isRisky) {
            return $inserted;
        }

        // fix the candidates in reverse order when applicable
        for ($i = \count($candidates) - 1; $i >= 0; --$i) {
            $index = $candidates[$i];

            // Check if returning only a variable (i.e. not the result of an expression, function call etc.)
            $returnVarIndex = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$returnVarIndex]->isGivenKind(T_VARIABLE)) {
                continue; // example: "return 1;"
            }

            $endReturnVarIndex = $tokens->getNextMeaningfulToken($returnVarIndex);
            if (!$tokens[$endReturnVarIndex]->equalsAny([';', [T_CLOSE_TAG]])) {
                continue; // example: "return $a + 1;"
            }

            // Check that the variable is assigned just before it is returned
            $assignVarEndIndex = $tokens->getPrevMeaningfulToken($index);
            if (!$tokens[$assignVarEndIndex]->equals(';')) {
                continue; // example: "? return $a;"
            }

            // Note: here we are @ "; return $a;" (or "; return $a ? >")
            do {
                $prevMeaningFul = $tokens->getPrevMeaningfulToken($assignVarEndIndex);

                if (!$tokens[$prevMeaningFul]->equals(')')) {
                    break;
                }

                $assignVarEndIndex = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $prevMeaningFul);
            } while (true);

            $assignVarOperatorIndex = $tokens->getPrevTokenOfKind(
                $assignVarEndIndex,
                ['=', ';', '{', [T_OPEN_TAG], [T_OPEN_TAG_WITH_ECHO]]
            );

            if (null === $assignVarOperatorIndex || !$tokens[$assignVarOperatorIndex]->equals('=')) {
                continue;
            }

            // Note: here we are @ "= [^;{<? ? >] ; return $a;"
            $assignVarIndex = $tokens->getPrevMeaningfulToken($assignVarOperatorIndex);
            if (!$tokens[$assignVarIndex]->equals($tokens[$returnVarIndex], false)) {
                continue;
            }

            // Note: here we are @ "$a = [^;{<? ? >] ; return $a;"
            $beforeAssignVarIndex = $tokens->getPrevMeaningfulToken($assignVarIndex);
            if (!$tokens[$beforeAssignVarIndex]->equalsAny([';', '{', '}'])) {
                continue;
            }

            // Note: here we are @ "[;{}] $a = [^;{<? ? >] ; return $a;"
            $inserted += $this->simplifyReturnStatement(
                $tokens,
                $assignVarIndex,
                $assignVarOperatorIndex,
                $index,
                $endReturnVarIndex
            );
        }

        return $inserted;
    }

    /**
     * @param int $assignVarIndex
     * @param int $assignVarOperatorIndex
     * @param int $returnIndex
     * @param int $returnVarEndIndex
     *
     * @return int >= 0 number of tokens inserted into the Tokens collection
     */
    private function simplifyReturnStatement(
        Tokens $tokens,
        $assignVarIndex,
        $assignVarOperatorIndex,
        $returnIndex,
        $returnVarEndIndex
    ) {
        $inserted = 0;
        $originalIndent = $tokens[$assignVarIndex - 1]->isWhitespace()
            ? $tokens[$assignVarIndex - 1]->getContent()
            : null
        ;

        // remove the return statement
        if ($tokens[$returnVarEndIndex]->equals(';')) { // do not remove PHP close tags
            $tokens->clearTokenAndMergeSurroundingWhitespace($returnVarEndIndex);
        }

        for ($i = $returnIndex; $i <= $returnVarEndIndex - 1; ++$i) {
            $this->clearIfSave($tokens, $i);
        }

        // remove no longer needed indentation of the old/remove return statement
        if ($tokens[$returnIndex - 1]->isWhitespace()) {
            $content = $tokens[$returnIndex - 1]->getContent();
            $fistLinebreakPos = strrpos($content, "\n");
            $content = false === $fistLinebreakPos
                ? ' '
                : substr($content, $fistLinebreakPos)
            ;

            $tokens[$returnIndex - 1] = new Token([T_WHITESPACE, $content]);
        }

        // remove the variable and the assignment
        for ($i = $assignVarIndex; $i <= $assignVarOperatorIndex; ++$i) {
            $this->clearIfSave($tokens, $i);
        }

        // insert new return statement
        $tokens->insertAt($assignVarIndex, new Token([T_RETURN, 'return']));
        ++$inserted;

        // use the original indent of the var assignment for the new return statement
        if (
            null !== $originalIndent
            && $tokens[$assignVarIndex - 1]->isWhitespace()
            && $originalIndent !== $tokens[$assignVarIndex - 1]->getContent()
        ) {
            $tokens[$assignVarIndex - 1] = new Token([T_WHITESPACE, $originalIndent]);
        }

        // remove trailing space after the new return statement which might be added during the clean up process
        $nextIndex = $tokens->getNonEmptySibling($assignVarIndex, 1);
        if (!$tokens[$nextIndex]->isWhitespace()) {
            $tokens->insertAt($nextIndex, new Token([T_WHITESPACE, ' ']));
            ++$inserted;
        }

        return $inserted;
    }

    private function clearIfSave(Tokens $tokens, $index)
    {
        if ($tokens[$index]->isComment()) {
            return;
        }

        if ($tokens[$index]->isWhitespace() && $tokens[$tokens->getPrevNonWhitespace($index)]->isComment()) {
            return;
        }

        $tokens->clearTokenAndMergeSurroundingWhitespace($index);
    }

    /**
     * @return bool
     */
    private function isSuperGlobal(Token $token)
    {
        static $superNames = [
            '$_COOKIE' => true,
            '$_ENV' => true,
            '$_FILES' => true,
            '$_GET' => true,
            '$_POST' => true,
            '$_REQUEST' => true,
            '$_SERVER' => true,
            '$_SESSION' => true,
            '$GLOBALS' => true,
        ];

        if (!$token->isGivenKind(T_VARIABLE)) {
            return false;
        }

        return isset($superNames[strtoupper($token->getContent())]);
    }
}
