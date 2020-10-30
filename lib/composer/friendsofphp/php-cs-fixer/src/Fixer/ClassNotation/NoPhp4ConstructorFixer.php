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

namespace PhpCsFixer\Fixer\ClassNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @author Matteo Beccati <matteo@beccati.com>
 */
final class NoPhp4ConstructorFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Convert PHP4-style constructors to `__construct`.',
            [
                new CodeSample('<?php
class Foo
{
    public function Foo($bar)
    {
    }
}
'),
            ],
            null,
            'Risky when old style constructor being fixed is overridden or overrides parent one.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before OrderedClassElementsFixer.
     */
    public function getPriority()
    {
        return 75;
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
    public function isRisky()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);
        $classes = array_keys($tokens->findGivenKind(T_CLASS));
        $numClasses = \count($classes);

        for ($i = 0; $i < $numClasses; ++$i) {
            $index = $classes[$i];

            // is it an an anonymous class definition?
            if ($tokensAnalyzer->isAnonymousClass($index)) {
                continue;
            }

            // is it inside a namespace?
            $nspIndex = $tokens->getPrevTokenOfKind($index, [[T_NAMESPACE, 'namespace']]);
            if (null !== $nspIndex) {
                $nspIndex = $tokens->getNextMeaningfulToken($nspIndex);

                // make sure it's not the global namespace, as PHP4 constructors are allowed in there
                if (!$tokens[$nspIndex]->equals('{')) {
                    // unless it's the global namespace, the index currently points to the name
                    $nspIndex = $tokens->getNextTokenOfKind($nspIndex, [';', '{']);

                    if ($tokens[$nspIndex]->equals(';')) {
                        // the class is inside a (non-block) namespace, no PHP4-code should be in there
                        break;
                    }

                    // the index points to the { of a block-namespace
                    $nspEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $nspIndex);
                    if ($index < $nspEnd) {
                        // the class is inside a block namespace, skip other classes that might be in it
                        for ($j = $i + 1; $j < $numClasses; ++$j) {
                            if ($classes[$j] < $nspEnd) {
                                ++$i;
                            }
                        }
                        // and continue checking the classes that might follow
                        continue;
                    }
                }
            }

            $classNameIndex = $tokens->getNextMeaningfulToken($index);
            $className = $tokens[$classNameIndex]->getContent();
            $classStart = $tokens->getNextTokenOfKind($classNameIndex, ['{']);
            $classEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $classStart);

            $this->fixConstructor($tokens, $className, $classStart, $classEnd);
            $this->fixParent($tokens, $classStart, $classEnd);
        }
    }

    /**
     * Fix constructor within a class, if possible.
     *
     * @param Tokens $tokens     the Tokens instance
     * @param string $className  the class name
     * @param int    $classStart the class start index
     * @param int    $classEnd   the class end index
     */
    private function fixConstructor(Tokens $tokens, $className, $classStart, $classEnd)
    {
        $php4 = $this->findFunction($tokens, $className, $classStart, $classEnd);

        if (null === $php4) {
            // no PHP4-constructor!
            return;
        }

        if (!empty($php4['modifiers'][T_ABSTRACT]) || !empty($php4['modifiers'][T_STATIC])) {
            // PHP4 constructor can't be abstract or static
            return;
        }

        $php5 = $this->findFunction($tokens, '__construct', $classStart, $classEnd);

        if (null === $php5) {
            // no PHP5-constructor, we can rename the old one to __construct
            $tokens[$php4['nameIndex']] = new Token([T_STRING, '__construct']);

            // in some (rare) cases we might have just created an infinite recursion issue
            $this->fixInfiniteRecursion($tokens, $php4['bodyIndex'], $php4['endIndex']);

            return;
        }

        // does the PHP4-constructor only call $this->__construct($args, ...)?
        list($seq, $case) = $this->getWrapperMethodSequence($tokens, '__construct', $php4['startIndex'], $php4['bodyIndex']);
        if (null !== $tokens->findSequence($seq, $php4['bodyIndex'] - 1, $php4['endIndex'], $case)) {
            // good, delete it!
            for ($i = $php4['startIndex']; $i <= $php4['endIndex']; ++$i) {
                $tokens->clearAt($i);
            }

            return;
        }

        // does __construct only call the PHP4-constructor (with the same args)?
        list($seq, $case) = $this->getWrapperMethodSequence($tokens, $className, $php4['startIndex'], $php4['bodyIndex']);
        if (null !== $tokens->findSequence($seq, $php5['bodyIndex'] - 1, $php5['endIndex'], $case)) {
            // that was a weird choice, but we can safely delete it and...
            for ($i = $php5['startIndex']; $i <= $php5['endIndex']; ++$i) {
                $tokens->clearAt($i);
            }
            // rename the PHP4 one to __construct
            $tokens[$php4['nameIndex']] = new Token([T_STRING, '__construct']);
        }
    }

    /**
     * Fix calls to the parent constructor within a class.
     *
     * @param Tokens $tokens     the Tokens instance
     * @param int    $classStart the class start index
     * @param int    $classEnd   the class end index
     */
    private function fixParent(Tokens $tokens, $classStart, $classEnd)
    {
        // check calls to the parent constructor
        foreach ($tokens->findGivenKind(T_EXTENDS) as $index => $token) {
            $parentIndex = $tokens->getNextMeaningfulToken($index);
            $parentClass = $tokens[$parentIndex]->getContent();

            // using parent::ParentClassName() or ParentClassName::ParentClassName()
            $parentSeq = $tokens->findSequence([
                [T_STRING],
                [T_DOUBLE_COLON],
                [T_STRING, $parentClass],
                '(',
            ], $classStart, $classEnd, [2 => false]);

            if (null !== $parentSeq) {
                // we only need indexes
                $parentSeq = array_keys($parentSeq);

                // match either of the possibilities
                if ($tokens[$parentSeq[0]]->equalsAny([[T_STRING, 'parent'], [T_STRING, $parentClass]], false)) {
                    // replace with parent::__construct
                    $tokens[$parentSeq[0]] = new Token([T_STRING, 'parent']);
                    $tokens[$parentSeq[2]] = new Token([T_STRING, '__construct']);
                }
            }

            // using $this->ParentClassName()
            $parentSeq = $tokens->findSequence([
                [T_VARIABLE, '$this'],
                [T_OBJECT_OPERATOR],
                [T_STRING, $parentClass],
                '(',
            ], $classStart, $classEnd, [2 => false]);

            if (null !== $parentSeq) {
                // we only need indexes
                $parentSeq = array_keys($parentSeq);

                // replace call with parent::__construct()
                $tokens[$parentSeq[0]] = new Token([
                    T_STRING,
                    'parent',
                ]);
                $tokens[$parentSeq[1]] = new Token([
                    T_DOUBLE_COLON,
                    '::',
                ]);
                $tokens[$parentSeq[2]] = new Token([T_STRING, '__construct']);
            }
        }
    }

    /**
     * Fix a particular infinite recursion issue happening when the parent class has __construct and the child has only
     * a PHP4 constructor that calls the parent constructor as $this->__construct().
     *
     * @param Tokens $tokens the Tokens instance
     * @param int    $start  the PHP4 constructor body start
     * @param int    $end    the PHP4 constructor body end
     */
    private function fixInfiniteRecursion(Tokens $tokens, $start, $end)
    {
        $seq = [
            [T_VARIABLE, '$this'],
            [T_OBJECT_OPERATOR],
            [T_STRING, '__construct'],
        ];

        while (true) {
            $callSeq = $tokens->findSequence($seq, $start, $end, [2 => false]);

            if (null === $callSeq) {
                return;
            }

            $callSeq = array_keys($callSeq);

            $tokens[$callSeq[0]] = new Token([T_STRING, 'parent']);
            $tokens[$callSeq[1]] = new Token([T_DOUBLE_COLON, '::']);
        }
    }

    /**
     * Generate the sequence of tokens necessary for the body of a wrapper method that simply
     * calls $this->{$method}( [args...] ) with the same arguments as its own signature.
     *
     * @param Tokens $tokens     the Tokens instance
     * @param string $method     the wrapped method name
     * @param int    $startIndex function/method start index
     * @param int    $bodyIndex  function/method body index
     *
     * @return array an array containing the sequence and case sensitiveness [ 0 => $seq, 1 => $case ]
     */
    private function getWrapperMethodSequence(Tokens $tokens, $method, $startIndex, $bodyIndex)
    {
        // initialise sequence as { $this->{$method}(
        $seq = [
            '{',
            [T_VARIABLE, '$this'],
            [T_OBJECT_OPERATOR],
            [T_STRING, $method],
            '(',
        ];
        $case = [3 => false];

        // parse method parameters, if any
        $index = $startIndex;
        while (true) {
            // find the next variable name
            $index = $tokens->getNextTokenOfKind($index, [[T_VARIABLE]]);

            if (null === $index || $index >= $bodyIndex) {
                // we've reached the body already
                break;
            }

            // append a comma if it's not the first variable
            if (\count($seq) > 5) {
                $seq[] = ',';
            }

            // append variable name to the sequence
            $seq[] = [T_VARIABLE, $tokens[$index]->getContent()];
        }

        // almost done, close the sequence with ); }
        $seq[] = ')';
        $seq[] = ';';
        $seq[] = '}';

        return [$seq, $case];
    }

    /**
     * Find a function or method matching a given name within certain bounds.
     *
     * @param Tokens $tokens     the Tokens instance
     * @param string $name       the function/Method name
     * @param int    $startIndex the search start index
     * @param int    $endIndex   the search end index
     *
     * @return null|array An associative array, if a match is found:
     *
     *     - nameIndex (int): The index of the function/method name.
     *     - startIndex (int): The index of the function/method start.
     *     - endIndex (int): The index of the function/method end.
     *     - bodyIndex (int): The index of the function/method body.
     *     - modifiers (array): The modifiers as array keys and their index as
     *       the values, e.g. array(T_PUBLIC => 10)
     */
    private function findFunction(Tokens $tokens, $name, $startIndex, $endIndex)
    {
        $function = $tokens->findSequence([
            [T_FUNCTION],
            [T_STRING, $name],
            '(',
        ], $startIndex, $endIndex, false);

        if (null === $function) {
            return null;
        }

        // keep only the indexes
        $function = array_keys($function);

        // find previous block, saving method modifiers for later use
        $possibleModifiers = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL];
        $modifiers = [];

        $prevBlock = $tokens->getPrevMeaningfulToken($function[0]);
        while (null !== $prevBlock && $tokens[$prevBlock]->isGivenKind($possibleModifiers)) {
            $modifiers[$tokens[$prevBlock]->getId()] = $prevBlock;
            $prevBlock = $tokens->getPrevMeaningfulToken($prevBlock);
        }

        if (isset($modifiers[T_ABSTRACT])) {
            // abstract methods have no body
            $bodyStart = null;
            $funcEnd = $tokens->getNextTokenOfKind($function[2], [';']);
        } else {
            // find method body start and the end of the function definition
            $bodyStart = $tokens->getNextTokenOfKind($function[2], ['{']);
            $funcEnd = null !== $bodyStart ? $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $bodyStart) : null;
        }

        return [
            'nameIndex' => $function[1],
            'startIndex' => $prevBlock + 1,
            'endIndex' => $funcEnd,
            'bodyIndex' => $bodyStart,
            'modifiers' => $modifiers,
        ];
    }
}
