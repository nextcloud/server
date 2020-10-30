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
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class PhpdocToReturnTypeFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * @var array<int, array<int, int|string>>
     */
    private $blacklistFuncNames = [
        [T_STRING, '__construct'],
        [T_STRING, '__destruct'],
        [T_STRING, '__clone'],
    ];

    /**
     * @var array<string, int>
     */
    private $versionSpecificTypes = [
        'void' => 70100,
        'iterable' => 70100,
        'object' => 70200,
    ];

    /**
     * @var array<string, string>
     */
    private $scalarTypes = [
        'bool' => 'bool',
        'true' => 'bool',
        'false' => 'bool',
        'float' => 'float',
        'int' => 'int',
        'string' => 'string',
    ];

    /**
     * @var array<string, bool>
     */
    private $skippedTypes = [
        'mixed' => true,
        'resource' => true,
        'null' => true,
    ];

    /**
     * @var string
     */
    private $classRegex = '/^\\\\?[a-zA-Z_\\x7f-\\xff](?:\\\\?[a-zA-Z0-9_\\x7f-\\xff]+)*(?<array>\[\])*$/';

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'EXPERIMENTAL: Takes `@return` annotation of non-mixed types and adjusts accordingly the function signature. Requires PHP >= 7.0.',
            [
                new VersionSpecificCodeSample(
                    '<?php

/** @return \My\Bar */
function my_foo()
{}
',
                    new VersionSpecification(70000)
                ),
                new VersionSpecificCodeSample(
                    '<?php

/** @return void */
function my_foo()
{}
',
                    new VersionSpecification(70100)
                ),
                new VersionSpecificCodeSample(
                    '<?php

/** @return object */
function my_foo()
{}
',
                    new VersionSpecification(70200)
                ),
            ],
            null,
            '[1] This rule is EXPERIMENTAL and is not covered with backward compatibility promise. [2] `@return` annotation is mandatory for the fixer to make changes, signatures of methods without it (no docblock, inheritdocs) will not be fixed. [3] Manual actions are required if inherited signatures are not properly documented. [4] `@inheritdocs` support is under construction.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        if (\PHP_VERSION_ID >= 70400 && $tokens->isTokenKindFound(T_FN)) {
            return true;
        }

        return \PHP_VERSION_ID >= 70000 && $tokens->isTokenKindFound(T_FUNCTION);
    }

    /**
     * {@inheritdoc}
     *
     * Must run before FullyQualifiedStrictTypesFixer, NoSuperfluousPhpdocTagsFixer, PhpdocAlignFixer, ReturnTypeDeclarationFixer.
     * Must run after CommentToPhpdocFixer, PhpdocIndentFixer, PhpdocScalarFixer, PhpdocScalarFixer, PhpdocToCommentFixer, PhpdocTypesFixer, PhpdocTypesFixer.
     */
    public function getPriority()
    {
        return 13;
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
            if (
                !$tokens[$index]->isGivenKind(T_FUNCTION)
                && (\PHP_VERSION_ID < 70400 || !$tokens[$index]->isGivenKind(T_FN))
            ) {
                continue;
            }

            $funcName = $tokens->getNextMeaningfulToken($index);
            if ($tokens[$funcName]->equalsAny($this->blacklistFuncNames, false)) {
                continue;
            }

            $returnTypeAnnotation = $this->findReturnAnnotations($tokens, $index);
            if (1 !== \count($returnTypeAnnotation)) {
                continue;
            }

            $returnTypeAnnotation = current($returnTypeAnnotation);
            $types = array_values($returnTypeAnnotation->getTypes());
            $typesCount = \count($types);

            if (1 > $typesCount || 2 < $typesCount) {
                continue;
            }

            $isNullable = false;
            $returnType = current($types);

            if (2 === $typesCount) {
                $null = $types[0];
                $returnType = $types[1];
                if ('null' !== $null) {
                    $null = $types[1];
                    $returnType = $types[0];
                }

                if ('null' !== $null) {
                    continue;
                }

                $isNullable = true;

                if (\PHP_VERSION_ID < 70100) {
                    continue;
                }

                if ('void' === $returnType) {
                    continue;
                }
            }

            if ('static' === $returnType) {
                $returnType = 'self';
            }

            if (isset($this->skippedTypes[$returnType])) {
                continue;
            }

            if (isset($this->versionSpecificTypes[$returnType]) && \PHP_VERSION_ID < $this->versionSpecificTypes[$returnType]) {
                continue;
            }

            if (isset($this->scalarTypes[$returnType])) {
                if (false === $this->configuration['scalar_types']) {
                    continue;
                }

                $returnType = $this->scalarTypes[$returnType];
            } else {
                if (1 !== Preg::match($this->classRegex, $returnType, $matches)) {
                    continue;
                }

                if (isset($matches['array'])) {
                    $returnType = 'array';
                }
            }

            $startIndex = $tokens->getNextTokenOfKind($index, ['{', ';']);

            if ($this->hasReturnTypeHint($tokens, $startIndex)) {
                continue;
            }

            $this->fixFunctionDefinition($tokens, $startIndex, $isNullable, $returnType);
        }
    }

    /**
     * Determine whether the function already has a return type hint.
     *
     * @param int $index The index of the end of the function definition line, EG at { or ;
     *
     * @return bool
     */
    private function hasReturnTypeHint(Tokens $tokens, $index)
    {
        $endFuncIndex = $tokens->getPrevTokenOfKind($index, [')']);
        $nextIndex = $tokens->getNextMeaningfulToken($endFuncIndex);

        return $tokens[$nextIndex]->isGivenKind(CT::T_TYPE_COLON);
    }

    /**
     * @param int    $index      The index of the end of the function definition line, EG at { or ;
     * @param bool   $isNullable
     * @param string $returnType
     */
    private function fixFunctionDefinition(Tokens $tokens, $index, $isNullable, $returnType)
    {
        static $specialTypes = [
            'array' => [CT::T_ARRAY_TYPEHINT, 'array'],
            'callable' => [T_CALLABLE, 'callable'],
        ];
        $newTokens = [
            new Token([CT::T_TYPE_COLON, ':']),
            new Token([T_WHITESPACE, ' ']),
        ];
        if (true === $isNullable) {
            $newTokens[] = new Token([CT::T_NULLABLE_TYPE, '?']);
        }

        if (isset($specialTypes[$returnType])) {
            $newTokens[] = new Token($specialTypes[$returnType]);
        } else {
            foreach (explode('\\', $returnType) as $nsIndex => $value) {
                if (0 === $nsIndex && '' === $value) {
                    continue;
                }

                if (0 < $nsIndex) {
                    $newTokens[] = new Token([T_NS_SEPARATOR, '\\']);
                }
                $newTokens[] = new Token([T_STRING, $value]);
            }
        }

        $endFuncIndex = $tokens->getPrevTokenOfKind($index, [')']);
        $tokens->insertAt($endFuncIndex + 1, $newTokens);
    }

    /**
     * Find all the return annotations in the function's PHPDoc comment.
     *
     * @param int $index The index of the function token
     *
     * @return Annotation[]
     */
    private function findReturnAnnotations(Tokens $tokens, $index)
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

        return $doc->getAnnotationsOfType('return');
    }
}
