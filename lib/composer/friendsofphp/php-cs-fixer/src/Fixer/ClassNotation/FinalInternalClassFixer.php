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
use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use Symfony\Component\OptionsResolver\Options;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author SpacePossum
 */
final class FinalInternalClassFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        $intersect = array_intersect_assoc(
            $this->configuration['annotation-white-list'],
            $this->configuration['annotation-black-list']
        );

        if (\count($intersect)) {
            throw new InvalidFixerConfigurationException($this->getName(), sprintf('Annotation cannot be used in both the white- and black list, got duplicates: "%s".', implode('", "', array_keys($intersect))));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Internal classes should be `final`.',
            [
                new CodeSample("<?php\n/**\n * @internal\n */\nclass Sample\n{\n}\n"),
                new CodeSample(
                    "<?php\n/** @CUSTOM */class A{}\n",
                    [
                        'annotation-white-list' => ['@Custom'],
                    ]
                ),
            ],
            null,
            'Changing classes to `final` might cause code execution to break.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before FinalStaticAccessFixer, SelfStaticAccessorFixer.
     * Must run after PhpUnitInternalClassFixer.
     */
    public function getPriority()
    {
        return 0;
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
        for ($index = $tokens->count() - 1; 0 <= $index; --$index) {
            if (!$tokens[$index]->isGivenKind(T_CLASS) || !$this->isClassCandidate($tokens, $index)) {
                continue;
            }

            // make class final
            $tokens->insertAt(
                $index,
                [
                    new Token([T_FINAL, 'final']),
                    new Token([T_WHITESPACE, ' ']),
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $annotationsAsserts = [static function (array $values) {
            foreach ($values as $value) {
                if (!\is_string($value) || '' === $value) {
                    return false;
                }
            }

            return true;
        }];

        $annotationsNormalizer = static function (Options $options, array $value) {
            $newValue = [];
            foreach ($value as $key) {
                if ('@' === $key[0]) {
                    $key = substr($key, 1);
                }

                $newValue[strtolower($key)] = true;
            }

            return $newValue;
        };

        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('annotation-white-list', 'Class level annotations tags that must be set in order to fix the class. (case insensitive)'))
                ->setAllowedTypes(['array'])
                ->setAllowedValues($annotationsAsserts)
                ->setDefault(['@internal'])
                ->setNormalizer($annotationsNormalizer)
                ->getOption(),
            (new FixerOptionBuilder('annotation-black-list', 'Class level annotations tags that must be omitted to fix the class, even if all of the white list ones are used as well. (case insensitive)'))
                ->setAllowedTypes(['array'])
                ->setAllowedValues($annotationsAsserts)
                ->setDefault([
                    '@final',
                    '@Entity',
                    '@ORM\Entity',
                    '@ORM\Mapping\Entity',
                    '@Mapping\Entity',
                ])
                ->setNormalizer($annotationsNormalizer)
                ->getOption(),
            (new FixerOptionBuilder('consider-absent-docblock-as-internal-class', 'Should classes without any DocBlock be fixed to final?'))
                ->setAllowedTypes(['bool'])
                ->setDefault(false)
                ->getOption(),
        ]);
    }

    /**
     * @param int $index T_CLASS index
     *
     * @return bool
     */
    private function isClassCandidate(Tokens $tokens, $index)
    {
        if ($tokens[$tokens->getPrevMeaningfulToken($index)]->isGivenKind([T_ABSTRACT, T_FINAL, T_NEW])) {
            return false; // ignore class; it is abstract or already final
        }

        $docToken = $tokens[$tokens->getPrevNonWhitespace($index)];

        if (!$docToken->isGivenKind(T_DOC_COMMENT)) {
            return $this->configuration['consider-absent-docblock-as-internal-class'];
        }

        $doc = new DocBlock($docToken->getContent());
        $tags = [];

        foreach ($doc->getAnnotations() as $annotation) {
            Preg::match('/@\S+(?=\s|$)/', $annotation->getContent(), $matches);
            $tag = strtolower(substr(array_shift($matches), 1));
            foreach ($this->configuration['annotation-black-list'] as $tagStart => $true) {
                if (0 === strpos($tag, $tagStart)) {
                    return false; // ignore class: class-level PHPDoc contains tag that has been black listed through configuration
                }
            }

            $tags[$tag] = true;
        }

        foreach ($this->configuration['annotation-white-list'] as $tag => $true) {
            if (!isset($tags[$tag])) {
                return false; // ignore class: class-level PHPDoc does not contain all tags that has been white listed through configuration
            }
        }

        return true;
    }
}
