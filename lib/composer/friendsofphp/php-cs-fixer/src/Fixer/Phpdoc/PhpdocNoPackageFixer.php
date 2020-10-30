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

use PhpCsFixer\AbstractProxyFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;

/**
 * @author Graham Campbell <graham@alt-three.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpdocNoPackageFixer extends AbstractProxyFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            '`@package` and `@subpackage` annotations should be omitted from PHPDoc.',
            [
                new CodeSample(
                    '<?php
/**
 * @internal
 * @package Foo
 * subpackage Bar
 */
class Baz
{
}
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before NoEmptyPhpdocFixer, PhpdocAlignFixer, PhpdocOrderFixer, PhpdocSeparationFixer, PhpdocTrimFixer.
     * Must run after CommentToPhpdocFixer, PhpdocIndentFixer, PhpdocScalarFixer, PhpdocToCommentFixer, PhpdocTypesFixer.
     */
    public function getPriority()
    {
        return parent::getPriority();
    }

    /**
     * {@inheritdoc}
     */
    protected function createProxyFixers()
    {
        $fixer = new GeneralPhpdocAnnotationRemoveFixer();
        $fixer->configure(['annotations' => ['package', 'subpackage']]);

        return [$fixer];
    }
}
