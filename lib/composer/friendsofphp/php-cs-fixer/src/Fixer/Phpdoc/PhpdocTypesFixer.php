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

use PhpCsFixer\AbstractPhpdocTypesFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\AllowedValueSubset;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;

/**
 * @author Graham Campbell <graham@alt-three.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpdocTypesFixer extends AbstractPhpdocTypesFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * Available types, grouped.
     *
     * @var array<string,string[]>
     */
    private static $possibleTypes = [
        'simple' => [
            'array',
            'bool',
            'callable',
            'float',
            'int',
            'iterable',
            'null',
            'object',
            'string',
        ],
        'alias' => [
            'boolean',
            'callback',
            'double',
            'integer',
            'real',
        ],
        'meta' => [
            '$this',
            'false',
            'mixed',
            'parent',
            'resource',
            'scalar',
            'self',
            'static',
            'true',
            'void',
        ],
    ];

    /**
     * @var array string[]
     */
    private $typesToFix = [];

    /**
     * {@inheritdoc}
     */
    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        $this->typesToFix = array_merge(...array_map(static function ($group) {
            return self::$possibleTypes[$group];
        }, $this->configuration['groups']));
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'The correct case must be used for standard PHP types in PHPDoc.',
            [
                new CodeSample(
                    '<?php
/**
 * @param STRING|String[] $bar
 *
 * @return inT[]
 */
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before GeneralPhpdocAnnotationRemoveFixer, NoBlankLinesAfterPhpdocFixer, NoEmptyPhpdocFixer, NoSuperfluousPhpdocTagsFixer, PhpdocAddMissingParamAnnotationFixer, PhpdocAlignFixer, PhpdocAlignFixer, PhpdocInlineTagFixer, PhpdocLineSpanFixer, PhpdocNoAccessFixer, PhpdocNoAliasTagFixer, PhpdocNoEmptyReturnFixer, PhpdocNoPackageFixer, PhpdocNoUselessInheritdocFixer, PhpdocOrderFixer, PhpdocReturnSelfReferenceFixer, PhpdocScalarFixer, PhpdocSeparationFixer, PhpdocSingleLineVarSpacingFixer, PhpdocSummaryFixer, PhpdocToParamTypeFixer, PhpdocToReturnTypeFixer, PhpdocToReturnTypeFixer, PhpdocTrimConsecutiveBlankLineSeparationFixer, PhpdocTrimFixer, PhpdocTypesOrderFixer, PhpdocVarAnnotationCorrectOrderFixer, PhpdocVarWithoutNameFixer.
     * Must run after PhpdocAnnotationWithoutDotFixer, PhpdocIndentFixer.
     */
    public function getPriority()
    {
        /*
         * Should be run before all other docblock fixers apart from the
         * phpdoc_to_comment and phpdoc_indent fixer to make sure all fixers
         * apply correct indentation to new code they add. This should run
         * before alignment of params is done since this fixer might change
         * the type and thereby un-aligning the params. We also must run before
         * the phpdoc_scalar_fixer so that it can make changes after us.
         */
        return 16;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalize($type)
    {
        $lower = strtolower($type);

        if (\in_array($lower, $this->typesToFix, true)) {
            return $lower;
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $possibleGroups = array_keys(self::$possibleTypes);

        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('groups', 'Type groups to fix.'))
                ->setAllowedTypes(['array'])
                ->setAllowedValues([new AllowedValueSubset($possibleGroups)])
                ->setDefault($possibleGroups)
                ->getOption(),
        ]);
    }
}
