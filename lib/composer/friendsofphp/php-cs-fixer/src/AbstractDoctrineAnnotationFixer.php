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

namespace PhpCsFixer;

use PhpCsFixer\Doctrine\Annotation\Tokens as DoctrineAnnotationTokens;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\Tokenizer\Token as PhpToken;
use PhpCsFixer\Tokenizer\Tokens as PhpTokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @internal
 */
abstract class AbstractDoctrineAnnotationFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * @var array
     */
    private $classyElements;

    /**
     * {@inheritdoc}
     */
    public function isCandidate(PhpTokens $tokens)
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, PhpTokens $phpTokens)
    {
        // fetch indexes one time, this is safe as we never add or remove a token during fixing
        $analyzer = new TokensAnalyzer($phpTokens);
        $this->classyElements = $analyzer->getClassyElements();

        /** @var PhpToken $docCommentToken */
        foreach ($phpTokens->findGivenKind(T_DOC_COMMENT) as $index => $docCommentToken) {
            if (!$this->nextElementAcceptsDoctrineAnnotations($phpTokens, $index)) {
                continue;
            }

            $tokens = DoctrineAnnotationTokens::createFromDocComment(
                $docCommentToken,
                $this->configuration['ignored_tags']
            );
            $this->fixAnnotations($tokens);
            $phpTokens[$index] = new PhpToken([T_DOC_COMMENT, $tokens->getCode()]);
        }
    }

    /**
     * Fixes Doctrine annotations from the given PHPDoc style comment.
     */
    abstract protected function fixAnnotations(DoctrineAnnotationTokens $doctrineAnnotationTokens);

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('ignored_tags', 'List of tags that must not be treated as Doctrine Annotations.'))
                ->setAllowedTypes(['array'])
                ->setAllowedValues([static function ($values) {
                    foreach ($values as $value) {
                        if (!\is_string($value)) {
                            return false;
                        }
                    }

                    return true;
                }])
                ->setDefault([
                    // PHPDocumentor 1
                    'abstract',
                    'access',
                    'code',
                    'deprec',
                    'encode',
                    'exception',
                    'final',
                    'ingroup',
                    'inheritdoc',
                    'inheritDoc',
                    'magic',
                    'name',
                    'toc',
                    'tutorial',
                    'private',
                    'static',
                    'staticvar',
                    'staticVar',
                    'throw',

                    // PHPDocumentor 2
                    'api',
                    'author',
                    'category',
                    'copyright',
                    'deprecated',
                    'example',
                    'filesource',
                    'global',
                    'ignore',
                    'internal',
                    'license',
                    'link',
                    'method',
                    'package',
                    'param',
                    'property',
                    'property-read',
                    'property-write',
                    'return',
                    'see',
                    'since',
                    'source',
                    'subpackage',
                    'throws',
                    'todo',
                    'TODO',
                    'usedBy',
                    'uses',
                    'var',
                    'version',

                    // PHPUnit
                    'after',
                    'afterClass',
                    'backupGlobals',
                    'backupStaticAttributes',
                    'before',
                    'beforeClass',
                    'codeCoverageIgnore',
                    'codeCoverageIgnoreStart',
                    'codeCoverageIgnoreEnd',
                    'covers',
                    'coversDefaultClass',
                    'coversNothing',
                    'dataProvider',
                    'depends',
                    'expectedException',
                    'expectedExceptionCode',
                    'expectedExceptionMessage',
                    'expectedExceptionMessageRegExp',
                    'group',
                    'large',
                    'medium',
                    'preserveGlobalState',
                    'requires',
                    'runTestsInSeparateProcesses',
                    'runInSeparateProcess',
                    'small',
                    'test',
                    'testdox',
                    'ticket',
                    'uses',

                    // PHPCheckStyle
                    'SuppressWarnings',

                    // PHPStorm
                    'noinspection',

                    // PEAR
                    'package_version',

                    // PlantUML
                    'enduml',
                    'startuml',

                    // other
                    'fix',
                    'FIXME',
                    'fixme',
                    'override',
                ])
                ->getOption(),
        ]);
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function nextElementAcceptsDoctrineAnnotations(PhpTokens $tokens, $index)
    {
        do {
            $index = $tokens->getNextMeaningfulToken($index);

            if (null === $index) {
                return false;
            }
        } while ($tokens[$index]->isGivenKind([T_ABSTRACT, T_FINAL]));

        if ($tokens[$index]->isClassy()) {
            return true;
        }

        while ($tokens[$index]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE, T_FINAL, T_ABSTRACT])) {
            $index = $tokens->getNextMeaningfulToken($index);
        }

        return isset($this->classyElements[$index]);
    }
}
