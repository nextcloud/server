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

namespace PhpCsFixer\Tokenizer;

/**
 * Analyzer of Tokens collection.
 *
 * Its role is to provide the ability to analyze collection.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Gregor Harlan <gharlan@web.de>
 * @author SpacePossum
 *
 * @internal
 */
final class TokensAnalyzer
{
    /**
     * Tokens collection instance.
     *
     * @var Tokens
     */
    private $tokens;

    public function __construct(Tokens $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * Get indexes of methods and properties in classy code (classes, interfaces and traits).
     *
     * @return array[]
     */
    public function getClassyElements()
    {
        $this->tokens->rewind();
        $elements = [];

        for ($index = 1, $count = \count($this->tokens) - 2; $index < $count; ++$index) {
            if ($this->tokens[$index]->isClassy()) {
                list($index, $newElements) = $this->findClassyElements($index, $index);
                $elements += $newElements;
            }
        }

        ksort($elements);

        return $elements;
    }

    /**
     * Get indexes of namespace uses.
     *
     * @param bool $perNamespace Return namespace uses per namespace
     *
     * @return int[]|int[][]
     */
    public function getImportUseIndexes($perNamespace = false)
    {
        $tokens = $this->tokens;

        $tokens->rewind();

        $uses = [];
        $namespaceIndex = 0;

        for ($index = 0, $limit = $tokens->count(); $index < $limit; ++$index) {
            $token = $tokens[$index];

            if ($token->isGivenKind(T_NAMESPACE)) {
                $nextTokenIndex = $tokens->getNextTokenOfKind($index, [';', '{']);
                $nextToken = $tokens[$nextTokenIndex];

                if ($nextToken->equals('{')) {
                    $index = $nextTokenIndex;
                }

                if ($perNamespace) {
                    ++$namespaceIndex;
                }

                continue;
            }

            if ($token->isGivenKind(T_USE)) {
                $uses[$namespaceIndex][] = $index;
            }
        }

        if (!$perNamespace && isset($uses[$namespaceIndex])) {
            return $uses[$namespaceIndex];
        }

        return $uses;
    }

    /**
     * Check if there is an array at given index.
     *
     * @param int $index
     *
     * @return bool
     */
    public function isArray($index)
    {
        return $this->tokens[$index]->isGivenKind([T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN]);
    }

    /**
     * Check if the array at index is multiline.
     *
     * This only checks the root-level of the array.
     *
     * @param int $index
     *
     * @return bool
     */
    public function isArrayMultiLine($index)
    {
        if (!$this->isArray($index)) {
            throw new \InvalidArgumentException(sprintf('Not an array at given index %d.', $index));
        }

        $tokens = $this->tokens;

        // Skip only when its an array, for short arrays we need the brace for correct
        // level counting
        if ($tokens[$index]->isGivenKind(T_ARRAY)) {
            $index = $tokens->getNextMeaningfulToken($index);
        }

        $endIndex = $tokens[$index]->equals('(')
            ? $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index)
            : $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index)
        ;

        for (++$index; $index < $endIndex; ++$index) {
            $token = $tokens[$index];
            $blockType = Tokens::detectBlockType($token);

            if ($blockType && $blockType['isStart']) {
                $index = $tokens->findBlockEnd($blockType['type'], $index);

                continue;
            }

            if (
                $token->isWhitespace() &&
                !$tokens[$index - 1]->isGivenKind(T_END_HEREDOC) &&
                false !== strpos($token->getContent(), "\n")
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the attributes of the method under the given index.
     *
     * The array has the following items:
     * 'visibility' int|null  T_PRIVATE, T_PROTECTED or T_PUBLIC
     * 'static'     bool
     * 'abstract'   bool
     * 'final'      bool
     *
     * @param int $index Token index of the method (T_FUNCTION)
     *
     * @return array
     */
    public function getMethodAttributes($index)
    {
        $tokens = $this->tokens;
        $token = $tokens[$index];

        if (!$token->isGivenKind(T_FUNCTION)) {
            throw new \LogicException(sprintf('No T_FUNCTION at given index %d, got %s.', $index, $token->getName()));
        }

        $attributes = [
            'visibility' => null,
            'static' => false,
            'abstract' => false,
            'final' => false,
        ];

        for ($i = $index; $i >= 0; --$i) {
            $tokenIndex = $tokens->getPrevMeaningfulToken($i);

            $i = $tokenIndex;
            $token = $tokens[$tokenIndex];

            if ($token->isGivenKind(T_STATIC)) {
                $attributes['static'] = true;

                continue;
            }

            if ($token->isGivenKind(T_FINAL)) {
                $attributes['final'] = true;

                continue;
            }

            if ($token->isGivenKind(T_ABSTRACT)) {
                $attributes['abstract'] = true;

                continue;
            }

            // visibility

            if ($token->isGivenKind(T_PRIVATE)) {
                $attributes['visibility'] = T_PRIVATE;

                continue;
            }

            if ($token->isGivenKind(T_PROTECTED)) {
                $attributes['visibility'] = T_PROTECTED;

                continue;
            }

            if ($token->isGivenKind(T_PUBLIC)) {
                $attributes['visibility'] = T_PUBLIC;

                continue;
            }

            // found a meaningful token that is not part of
            // the function signature; stop looking
            break;
        }

        return $attributes;
    }

    /**
     * Check if there is an anonymous class under given index.
     *
     * @param int $index
     *
     * @return bool
     */
    public function isAnonymousClass($index)
    {
        $tokens = $this->tokens;
        $token = $tokens[$index];

        if (!$token->isClassy()) {
            throw new \LogicException(sprintf('No classy token at given index %d.', $index));
        }

        if (!$token->isGivenKind(T_CLASS)) {
            return false;
        }

        return $tokens[$tokens->getPrevMeaningfulToken($index)]->isGivenKind(T_NEW);
    }

    /**
     * Check if the function under given index is a lambda.
     *
     * @param int $index
     *
     * @return bool
     */
    public function isLambda($index)
    {
        if (
            !$this->tokens[$index]->isGivenKind(T_FUNCTION)
            && (\PHP_VERSION_ID < 70400 || !$this->tokens[$index]->isGivenKind(T_FN))
        ) {
            throw new \LogicException(sprintf('No T_FUNCTION or T_FN at given index %d, got %s.', $index, $this->tokens[$index]->getName()));
        }

        $startParenthesisIndex = $this->tokens->getNextMeaningfulToken($index);
        $startParenthesisToken = $this->tokens[$startParenthesisIndex];

        // skip & for `function & () {}` syntax
        if ($startParenthesisToken->isGivenKind(CT::T_RETURN_REF)) {
            $startParenthesisIndex = $this->tokens->getNextMeaningfulToken($startParenthesisIndex);
            $startParenthesisToken = $this->tokens[$startParenthesisIndex];
        }

        return $startParenthesisToken->equals('(');
    }

    /**
     * Check if the T_STRING under given index is a constant invocation.
     *
     * @param int $index
     *
     * @return bool
     */
    public function isConstantInvocation($index)
    {
        if (!$this->tokens[$index]->isGivenKind(T_STRING)) {
            throw new \LogicException(sprintf('No T_STRING at given index %d, got %s.', $index, $this->tokens[$index]->getName()));
        }

        $nextIndex = $this->tokens->getNextMeaningfulToken($index);

        if (
            $this->tokens[$nextIndex]->equalsAny(['(', '{']) ||
            $this->tokens[$nextIndex]->isGivenKind([T_AS, T_DOUBLE_COLON, T_ELLIPSIS, T_NS_SEPARATOR, CT::T_RETURN_REF, CT::T_TYPE_ALTERNATION, T_VARIABLE])
        ) {
            return false;
        }

        $prevIndex = $this->tokens->getPrevMeaningfulToken($index);

        if ($this->tokens[$prevIndex]->isGivenKind([T_AS, T_CLASS, T_CONST, T_DOUBLE_COLON, T_FUNCTION, T_GOTO, CT::T_GROUP_IMPORT_BRACE_OPEN, T_INTERFACE, T_OBJECT_OPERATOR, T_TRAIT, CT::T_TYPE_COLON])) {
            return false;
        }

        while ($this->tokens[$prevIndex]->isGivenKind([CT::T_NAMESPACE_OPERATOR, T_NS_SEPARATOR, T_STRING])) {
            $prevIndex = $this->tokens->getPrevMeaningfulToken($prevIndex);
        }

        if ($this->tokens[$prevIndex]->isGivenKind([CT::T_CONST_IMPORT, T_EXTENDS, CT::T_FUNCTION_IMPORT, T_IMPLEMENTS, T_INSTANCEOF, T_INSTEADOF, T_NAMESPACE, T_NEW, CT::T_NULLABLE_TYPE, CT::T_TYPE_COLON, T_USE, CT::T_USE_TRAIT])) {
            return false;
        }

        // `FOO & $bar` could be:
        //   - function reference parameter: function baz(Foo & $bar) {}
        //   - bit operator: $x = FOO & $bar;
        if ($this->tokens[$nextIndex]->equals('&') && $this->tokens[$this->tokens->getNextMeaningfulToken($nextIndex)]->isGivenKind(T_VARIABLE)) {
            $checkIndex = $this->tokens->getPrevTokenOfKind($prevIndex, [';', '{', '}', [T_FUNCTION], [T_OPEN_TAG], [T_OPEN_TAG_WITH_ECHO]]);

            if ($this->tokens[$checkIndex]->isGivenKind(T_FUNCTION)) {
                return false;
            }
        }

        // check for `extends`/`implements`/`use` list
        if ($this->tokens[$prevIndex]->equals(',')) {
            $checkIndex = $prevIndex;
            while ($this->tokens[$checkIndex]->equalsAny([',', [T_AS], [CT::T_NAMESPACE_OPERATOR], [T_NS_SEPARATOR], [T_STRING]])) {
                $checkIndex = $this->tokens->getPrevMeaningfulToken($checkIndex);
            }

            if ($this->tokens[$checkIndex]->isGivenKind([T_EXTENDS, CT::T_GROUP_IMPORT_BRACE_OPEN, T_IMPLEMENTS, T_USE, CT::T_USE_TRAIT])) {
                return false;
            }
        }

        // check for array in double quoted string: `"..$foo[bar].."`
        if ($this->tokens[$prevIndex]->equals('[') && $this->tokens[$nextIndex]->equals(']')) {
            $checkToken = $this->tokens[$this->tokens->getNextMeaningfulToken($nextIndex)];

            if ($checkToken->equals('"') || $checkToken->isGivenKind([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES, T_ENCAPSED_AND_WHITESPACE, T_VARIABLE])) {
                return false;
            }
        }

        // check for goto label
        if ($this->tokens[$nextIndex]->equals(':') && $this->tokens[$prevIndex]->equalsAny([';', '}', [T_OPEN_TAG], [T_OPEN_TAG_WITH_ECHO]])) {
            return false;
        }

        return true;
    }

    /**
     * Checks if there is an unary successor operator under given index.
     *
     * @param int $index
     *
     * @return bool
     */
    public function isUnarySuccessorOperator($index)
    {
        static $allowedPrevToken = [
            ']',
            [T_STRING],
            [T_VARIABLE],
            [CT::T_ARRAY_INDEX_CURLY_BRACE_CLOSE],
            [CT::T_DYNAMIC_PROP_BRACE_CLOSE],
            [CT::T_DYNAMIC_VAR_BRACE_CLOSE],
        ];

        $tokens = $this->tokens;
        $token = $tokens[$index];

        if (!$token->isGivenKind([T_INC, T_DEC])) {
            return false;
        }

        $prevToken = $tokens[$tokens->getPrevMeaningfulToken($index)];

        return $prevToken->equalsAny($allowedPrevToken);
    }

    /**
     * Checks if there is an unary predecessor operator under given index.
     *
     * @param int $index
     *
     * @return bool
     */
    public function isUnaryPredecessorOperator($index)
    {
        static $potentialSuccessorOperator = [T_INC, T_DEC];

        static $potentialBinaryOperator = ['+', '-', '&', [CT::T_RETURN_REF]];

        static $otherOperators;
        if (null === $otherOperators) {
            $otherOperators = ['!', '~', '@', [T_ELLIPSIS]];
        }

        static $disallowedPrevTokens;
        if (null === $disallowedPrevTokens) {
            $disallowedPrevTokens = [
                ']',
                '}',
                ')',
                '"',
                '`',
                [CT::T_ARRAY_SQUARE_BRACE_CLOSE],
                [CT::T_ARRAY_INDEX_CURLY_BRACE_CLOSE],
                [CT::T_DYNAMIC_PROP_BRACE_CLOSE],
                [CT::T_DYNAMIC_VAR_BRACE_CLOSE],
                [T_CLASS_C],
                [T_CONSTANT_ENCAPSED_STRING],
                [T_DEC],
                [T_DIR],
                [T_DNUMBER],
                [T_FILE],
                [T_FUNC_C],
                [T_INC],
                [T_LINE],
                [T_LNUMBER],
                [T_METHOD_C],
                [T_NS_C],
                [T_STRING],
                [T_TRAIT_C],
                [T_VARIABLE],
            ];
        }

        $tokens = $this->tokens;
        $token = $tokens[$index];

        if ($token->isGivenKind($potentialSuccessorOperator)) {
            return !$this->isUnarySuccessorOperator($index);
        }

        if ($token->equalsAny($otherOperators)) {
            return true;
        }

        if (!$token->equalsAny($potentialBinaryOperator)) {
            return false;
        }

        $prevToken = $tokens[$tokens->getPrevMeaningfulToken($index)];

        if (!$prevToken->equalsAny($disallowedPrevTokens)) {
            return true;
        }

        if (!$token->equals('&') || !$prevToken->isGivenKind(T_STRING)) {
            return false;
        }

        static $searchTokens = [
            ';',
            '{',
            '}',
            [T_FUNCTION],
            [T_OPEN_TAG],
            [T_OPEN_TAG_WITH_ECHO],
        ];
        $prevToken = $tokens[$tokens->getPrevTokenOfKind($index, $searchTokens)];

        return $prevToken->isGivenKind(T_FUNCTION);
    }

    /**
     * Checks if there is a binary operator under given index.
     *
     * @param int $index
     *
     * @return bool
     */
    public function isBinaryOperator($index)
    {
        static $nonArrayOperators = [
            '=' => true,
            '*' => true,
            '/' => true,
            '%' => true,
            '<' => true,
            '>' => true,
            '|' => true,
            '^' => true,
            '.' => true,
        ];

        static $potentialUnaryNonArrayOperators = [
            '+' => true,
            '-' => true,
            '&' => true,
        ];

        static $arrayOperators;
        if (null === $arrayOperators) {
            $arrayOperators = [
                T_AND_EQUAL => true,            // &=
                T_BOOLEAN_AND => true,          // &&
                T_BOOLEAN_OR => true,           // ||
                T_CONCAT_EQUAL => true,         // .=
                T_DIV_EQUAL => true,            // /=
                T_DOUBLE_ARROW => true,         // =>
                T_IS_EQUAL => true,             // ==
                T_IS_GREATER_OR_EQUAL => true,  // >=
                T_IS_IDENTICAL => true,         // ===
                T_IS_NOT_EQUAL => true,         // !=, <>
                T_IS_NOT_IDENTICAL => true,     // !==
                T_IS_SMALLER_OR_EQUAL => true,  // <=
                T_LOGICAL_AND => true,          // and
                T_LOGICAL_OR => true,           // or
                T_LOGICAL_XOR => true,          // xor
                T_MINUS_EQUAL => true,          // -=
                T_MOD_EQUAL => true,            // %=
                T_MUL_EQUAL => true,            // *=
                T_OR_EQUAL => true,             // |=
                T_PLUS_EQUAL => true,           // +=
                T_POW => true,                  // **
                T_POW_EQUAL => true,            // **=
                T_SL => true,                   // <<
                T_SL_EQUAL => true,             // <<=
                T_SR => true,                   // >>
                T_SR_EQUAL => true,             // >>=
                T_XOR_EQUAL => true,            // ^=
                CT::T_TYPE_ALTERNATION => true, // |
            ];

            if (\defined('T_SPACESHIP')) {
                $arrayOperators[T_SPACESHIP] = true; // <=>
            }

            if (\defined('T_COALESCE')) {
                $arrayOperators[T_COALESCE] = true;  // ??
            }

            if (\defined('T_COALESCE_EQUAL')) {
                $arrayOperators[T_COALESCE_EQUAL] = true;  // ??=
            }
        }

        $tokens = $this->tokens;
        $token = $tokens[$index];

        if ($token->isArray()) {
            return isset($arrayOperators[$token->getId()]);
        }

        if (isset($nonArrayOperators[$token->getContent()])) {
            return true;
        }

        if (isset($potentialUnaryNonArrayOperators[$token->getContent()])) {
            return !$this->isUnaryPredecessorOperator($index);
        }

        return false;
    }

    /**
     * Check if `T_WHILE` token at given index is `do { ... } while ();` syntax
     * and not `while () { ...}`.
     *
     * @param int $index
     *
     * @return bool
     */
    public function isWhilePartOfDoWhile($index)
    {
        $tokens = $this->tokens;
        $token = $tokens[$index];

        if (!$token->isGivenKind(T_WHILE)) {
            throw new \LogicException(sprintf('No T_WHILE at given index %d, got %s.', $index, $token->getName()));
        }

        $endIndex = $tokens->getPrevMeaningfulToken($index);
        if (!$tokens[$endIndex]->equals('}')) {
            return false;
        }

        $startIndex = $tokens->findBlockStart(Tokens::BLOCK_TYPE_CURLY_BRACE, $endIndex);
        $beforeStartIndex = $tokens->getPrevMeaningfulToken($startIndex);

        return $tokens[$beforeStartIndex]->isGivenKind(T_DO);
    }

    /**
     * Find classy elements.
     *
     * Searches in tokens from the classy (start) index till the end (index) of the classy.
     * Returns an array; first value is the index until the method has analysed (int), second the found classy elements (array).
     *
     * @param int $classIndex classy index
     * @param int $index
     *
     * @return array
     */
    private function findClassyElements($classIndex, $index)
    {
        $elements = [];
        $curlyBracesLevel = 0;
        $bracesLevel = 0;
        ++$index; // skip the classy index itself

        for ($count = \count($this->tokens); $index < $count; ++$index) {
            $token = $this->tokens[$index];

            if ($token->isGivenKind(T_ENCAPSED_AND_WHITESPACE)) {
                continue;
            }

            if ($token->isClassy()) { // anonymous class in class
                // check for nested anonymous classes inside the new call of an anonymous class,
                // for example `new class(function (){new class(function (){new class(function (){}){};}){};}){};` etc.
                // if class(XYZ) {} skip till `(` as XYZ might contain functions etc.

                $nestedClassIndex = $index;
                $index = $this->tokens->getNextMeaningfulToken($index);

                if ($this->tokens[$index]->equals('(')) {
                    ++$index; // move after `(`

                    for ($nestedBracesLevel = 1; $index < $count; ++$index) {
                        $token = $this->tokens[$index];

                        if ($token->equals('(')) {
                            ++$nestedBracesLevel;

                            continue;
                        }

                        if ($token->equals(')')) {
                            --$nestedBracesLevel;

                            if (0 === $nestedBracesLevel) {
                                list($index, $newElements) = $this->findClassyElements($nestedClassIndex, $index);
                                $elements += $newElements;

                                break;
                            }

                            continue;
                        }

                        if ($token->isClassy()) { // anonymous class in class
                            list($index, $newElements) = $this->findClassyElements($index, $index);
                            $elements += $newElements;
                        }
                    }
                } else {
                    list($index, $newElements) = $this->findClassyElements($nestedClassIndex, $nestedClassIndex);
                    $elements += $newElements;
                }

                continue;
            }

            if ($token->equals('(')) {
                ++$bracesLevel;

                continue;
            }

            if ($token->equals(')')) {
                --$bracesLevel;

                continue;
            }

            if ($token->equals('{')) {
                ++$curlyBracesLevel;

                continue;
            }

            if ($token->equals('}')) {
                --$curlyBracesLevel;

                if (0 === $curlyBracesLevel) {
                    break;
                }

                continue;
            }

            if (1 !== $curlyBracesLevel || !$token->isArray()) {
                continue;
            }

            if (0 === $bracesLevel && $token->isGivenKind(T_VARIABLE)) {
                $elements[$index] = [
                    'token' => $token,
                    'type' => 'property',
                    'classIndex' => $classIndex,
                ];

                continue;
            }

            if ($token->isGivenKind(T_FUNCTION)) {
                $elements[$index] = [
                    'token' => $token,
                    'type' => 'method',
                    'classIndex' => $classIndex,
                ];
            } elseif ($token->isGivenKind(T_CONST)) {
                $elements[$index] = [
                    'token' => $token,
                    'type' => 'const',
                    'classIndex' => $classIndex,
                ];
            }
        }

        return [$index, $elements];
    }
}
