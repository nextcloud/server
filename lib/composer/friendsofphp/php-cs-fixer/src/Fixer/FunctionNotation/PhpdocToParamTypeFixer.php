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

namespace PhpCsFixer\Fixer\FunctionNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Jan Gantzert <jan@familie-gantzert.de>
 */
final class PhpdocToParamTypeFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /** @internal */
    const CLASS_REGEX = '/^\\\\?[a-zA-Z_\\x7f-\\xff](?:\\\\?[a-zA-Z0-9_\\x7f-\\xff]+)*(?<array>\[\])*$/';

    /** @internal */
    const MINIMUM_PHP_VERSION = 70000;

    /**
     * @var array{int, string}[]
     */
    private $blacklistFuncNames = [
        [T_STRING, '__clone'],
        [T_STRING, '__destruct'],
    ];

    /**
     * @var array<string, true>
     */
    private $skippedTypes = [
        'mixed' => true,
        'resource' => true,
        'static' => true,
    ];

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'EXPERIMENTAL: Takes `@param` annotations of non-mixed types and adjusts accordingly the function signature. Requires PHP >= 7.0.',
            [
                new VersionSpecificCodeSample(
                    '<?php

/** @param string $bar */
function my_foo($bar)
{}
',
                    new VersionSpecification(70000)
                ),
                new VersionSpecificCodeSample(
                    '<?php

/** @param string|null $bar */
function my_foo($bar)
{}
',
                    new VersionSpecification(70100)
                ),
            ],
            null,
            '[1] This rule is EXPERIMENTAL and is not covered with backward compatibility promise. [2] `@param` annotation is mandatory for the fixer to make changes, signatures of methods without it (no docblock, inheritdocs) will not be fixed. [3] Manual actions are required if inherited signatures are not properly documented.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return \PHP_VERSION_ID >= self::MINIMUM_PHP_VERSION && $tokens->isTokenKindFound(T_FUNCTION);
    }

    /**
     * {@inheritdoc}
     *
     * Must run before NoSuperfluousPhpdocTagsFixer, PhpdocAlignFixer.
     * Must run after CommentToPhpdocFixer, PhpdocIndentFixer, PhpdocScalarFixer, PhpdocToCommentFixer, PhpdocTypesFixer.
     */
    public function getPriority()
    {
        return 8;
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
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('scalar_types', 'Fix also scalar types; may have unexpected behaviour due to PHP bad type coercion system.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        for ($index = $tokens->count() - 1; 0 < $index; --$index) {
            if (!$tokens[$index]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $funcName = $tokens->getNextMeaningfulToken($index);
            if ($tokens[$funcName]->equalsAny($this->blacklistFuncNames, false)) {
                continue;
            }

            $paramTypeAnnotations = $this->findParamAnnotations($tokens, $index);

            foreach ($paramTypeAnnotations as $paramTypeAnnotation) {
                if (\PHP_VERSION_ID < self::MINIMUM_PHP_VERSION) {
                    continue;
                }

                $types = array_values($paramTypeAnnotation->getTypes());
                $paramType = current($types);

                if (isset($this->skippedTypes[$paramType])) {
                    continue;
                }

                $hasIterable = false;
                $hasNull = false;
                $hasVoid = false;
                $hasArray = false;
                $hasString = false;
                $hasInt = false;
                $hasFloat = false;
                $hasBool = false;
                $hasCallable = false;
                $hasObject = false;
                $minimumTokenPhpVersion = self::MINIMUM_PHP_VERSION;

                foreach ($types as $key => $type) {
                    if (1 !== Preg::match(self::CLASS_REGEX, $type, $matches)) {
                        continue;
                    }

                    if (isset($matches['array'])) {
                        $hasArray = true;
                        unset($types[$key]);
                    }

                    if ('iterable' === $type) {
                        $hasIterable = true;
                        unset($types[$key]);
                        $minimumTokenPhpVersion = 70100;
                    }

                    if ('null' === $type) {
                        $hasNull = true;
                        unset($types[$key]);
                        $minimumTokenPhpVersion = 70100;
                    }

                    if ('void' === $type) {
                        $hasVoid = true;
                        unset($types[$key]);
                    }

                    if ('string' === $type) {
                        $hasString = true;
                        unset($types[$key]);
                    }

                    if ('int' === $type) {
                        $hasInt = true;
                        unset($types[$key]);
                    }

                    if ('float' === $type) {
                        $hasFloat = true;
                        unset($types[$key]);
                    }

                    if ('bool' === $type) {
                        $hasBool = true;
                        unset($types[$key]);
                    }

                    if ('callable' === $type) {
                        $hasCallable = true;
                        unset($types[$key]);
                    }

                    if ('array' === $type) {
                        $hasArray = true;
                        unset($types[$key]);
                    }

                    if ('object' === $type) {
                        $hasObject = true;
                        unset($types[$key]);
                        $minimumTokenPhpVersion = 70200;
                    }
                }

                if (\PHP_VERSION_ID < $minimumTokenPhpVersion) {
                    continue;
                }

                $typesCount = \count($types);

                if (1 < $typesCount) {
                    continue;
                }

                if (0 === $typesCount) {
                    $paramType = '';
                } elseif (1 === $typesCount) {
                    $paramType = array_shift($types);
                }

                $startIndex = $tokens->getNextTokenOfKind($index, ['(']) + 1;
                $variableIndex = $this->findCorrectVariable($tokens, $startIndex - 1, $paramTypeAnnotation);

                if (null === $variableIndex) {
                    continue;
                }

                $byRefIndex = $tokens->getPrevMeaningfulToken($variableIndex);
                if ($tokens[$byRefIndex]->equals('&')) {
                    $variableIndex = $byRefIndex;
                }

                if (!('(' === $tokens[$variableIndex - 1]->getContent()) && $this->hasParamTypeHint($tokens, $variableIndex - 2)) {
                    continue;
                }

                $this->fixFunctionDefinition(
                    $paramType,
                    $tokens,
                    $variableIndex,
                    $hasNull,
                    $hasArray,
                    $hasIterable,
                    $hasVoid,
                    $hasString,
                    $hasInt,
                    $hasFloat,
                    $hasBool,
                    $hasCallable,
                    $hasObject
                );
            }
        }
    }

    /**
     * Find all the param annotations in the function's PHPDoc comment.
     *
     * @param int $index The index of the function token
     *
     * @return Annotation[]
     */
    private function findParamAnnotations(Tokens $tokens, $index)
    {
        do {
            $index = $tokens->getPrevNonWhitespace($index);
        } while ($tokens[$index]->isGivenKind([
            T_COMMENT,
            T_ABSTRACT,
            T_FINAL,
            T_PRIVATE,
            T_PROTECTED,
            T_PUBLIC,
            T_STATIC,
        ]));

        if (!$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
            return [];
        }

        $doc = new DocBlock($tokens[$index]->getContent());

        return $doc->getAnnotationsOfType('param');
    }

    /**
     * @param int        $index
     * @param Annotation $paramTypeAnnotation
     *
     * @return null|int
     */
    private function findCorrectVariable(Tokens $tokens, $index, $paramTypeAnnotation)
    {
        $nextFunction = $tokens->getNextTokenOfKind($index, [[T_FUNCTION]]);
        $variableIndex = $tokens->getNextTokenOfKind($index, [[T_VARIABLE]]);

        if (\is_int($nextFunction) && $variableIndex > $nextFunction) {
            return null;
        }

        if (!isset($tokens[$variableIndex])) {
            return null;
        }

        $variableToken = $tokens[$variableIndex]->getContent();
        Preg::match('/@param\s*[^\s!<]+\s*([^\s]+)/', $paramTypeAnnotation->getContent(), $paramVariable);
        if (isset($paramVariable[1]) && $paramVariable[1] === $variableToken) {
            return $variableIndex;
        }

        return $this->findCorrectVariable($tokens, $index + 1, $paramTypeAnnotation);
    }

    /**
     * Determine whether the function already has a param type hint.
     *
     * @param int $index The index of the end of the function definition line, EG at { or ;
     *
     * @return bool
     */
    private function hasParamTypeHint(Tokens $tokens, $index)
    {
        return $tokens[$index]->isGivenKind([T_STRING, T_NS_SEPARATOR, CT::T_ARRAY_TYPEHINT, T_CALLABLE, CT::T_NULLABLE_TYPE]);
    }

    /**
     * @param string $paramType
     * @param int    $index       The index of the end of the function definition line, EG at { or ;
     * @param bool   $hasNull
     * @param bool   $hasArray
     * @param bool   $hasIterable
     * @param bool   $hasVoid
     * @param bool   $hasString
     * @param bool   $hasInt
     * @param bool   $hasFloat
     * @param bool   $hasBool
     * @param bool   $hasCallable
     * @param bool   $hasObject
     */
    private function fixFunctionDefinition(
        $paramType,
        Tokens $tokens,
        $index,
        $hasNull,
        $hasArray,
        $hasIterable,
        $hasVoid,
        $hasString,
        $hasInt,
        $hasFloat,
        $hasBool,
        $hasCallable,
        $hasObject
    ) {
        $newTokens = [];

        if (true === $hasVoid) {
            $newTokens[] = new Token('void');
        } elseif (true === $hasIterable && true === $hasArray) {
            $newTokens[] = new Token([CT::T_ARRAY_TYPEHINT, 'array']);
        } elseif (true === $hasIterable) {
            $newTokens[] = new Token([T_STRING, 'iterable']);
        } elseif (true === $hasArray) {
            $newTokens[] = new Token([CT::T_ARRAY_TYPEHINT, 'array']);
        } elseif (true === $hasString) {
            $newTokens[] = new Token([T_STRING, 'string']);
        } elseif (true === $hasInt) {
            $newTokens[] = new Token([T_STRING, 'int']);
        } elseif (true === $hasFloat) {
            $newTokens[] = new Token([T_STRING, 'float']);
        } elseif (true === $hasBool) {
            $newTokens[] = new Token([T_STRING, 'bool']);
        } elseif (true === $hasCallable) {
            $newTokens[] = new Token([T_CALLABLE, 'callable']);
        } elseif (true === $hasObject) {
            $newTokens[] = new Token([T_STRING, 'object']);
        }

        if ('' !== $paramType && [] !== $newTokens) {
            return;
        }

        foreach (explode('\\', $paramType) as $nsIndex => $value) {
            if (0 === $nsIndex && '' === $value) {
                continue;
            }

            if (0 < $nsIndex) {
                $newTokens[] = new Token([T_NS_SEPARATOR, '\\']);
            }
            $newTokens[] = new Token([T_STRING, $value]);
        }

        if (true === $hasNull) {
            array_unshift($newTokens, new Token([CT::T_NULLABLE_TYPE, '?']));
        }

        $newTokens[] = new Token([T_WHITESPACE, ' ']);
        $tokens->insertAt($index, $newTokens);
    }
}
