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

namespace PhpCsFixer\Fixer\DoctrineAnnotation;

use Doctrine\Common\Annotations\DocLexer;
use PhpCsFixer\AbstractDoctrineAnnotationFixer;
use PhpCsFixer\Doctrine\Annotation\Tokens;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;

/**
 * Forces the configured operator for assignment in arrays in Doctrine Annotations.
 */
final class DoctrineAnnotationArrayAssignmentFixer extends AbstractDoctrineAnnotationFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Doctrine annotations must use configured operator for assignment in arrays.',
            [
                new CodeSample(
                    "<?php\n/**\n * @Foo({bar : \"baz\"})\n */\nclass Bar {}\n"
                ),
                new CodeSample(
                    "<?php\n/**\n * @Foo({bar = \"baz\"})\n */\nclass Bar {}\n",
                    ['operator' => ':']
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before DoctrineAnnotationSpacesFixer.
     */
    public function getPriority()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $options = parent::createConfigurationDefinition()->getOptions();

        $operator = new FixerOptionBuilder('operator', 'The operator to use.');
        $options[] = $operator
            ->setAllowedValues(['=', ':'])
            ->setDefault('=')
            ->getOption()
        ;

        return new FixerConfigurationResolver($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function fixAnnotations(Tokens $tokens)
    {
        $scopes = [];
        foreach ($tokens as $token) {
            if ($token->isType(DocLexer::T_OPEN_PARENTHESIS)) {
                $scopes[] = 'annotation';

                continue;
            }

            if ($token->isType(DocLexer::T_OPEN_CURLY_BRACES)) {
                $scopes[] = 'array';

                continue;
            }

            if ($token->isType([DocLexer::T_CLOSE_PARENTHESIS, DocLexer::T_CLOSE_CURLY_BRACES])) {
                array_pop($scopes);

                continue;
            }

            if ('array' === end($scopes) && $token->isType([DocLexer::T_EQUALS, DocLexer::T_COLON])) {
                $token->setContent($this->configuration['operator']);
            }
        }
    }
}
