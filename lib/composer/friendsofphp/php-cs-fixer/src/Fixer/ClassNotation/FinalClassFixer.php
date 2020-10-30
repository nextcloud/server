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

use PhpCsFixer\AbstractProxyFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class FinalClassFixer extends AbstractProxyFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'All classes must be final, except abstract ones and Doctrine entities.',
            [
                new CodeSample(
                    '<?php
class MyApp {}
'
                ),
            ],
            'No exception and no configuration are intentional. Beside Doctrine entities and of course abstract classes, there is no single reason not to declare all classes final. '
            .'If you want to subclass a class, mark the parent class as abstract and create two child classes, one empty if necessary: you\'ll gain much more fine grained type-hinting. '
            .'If you need to mock a standalone class, create an interface, or maybe it\'s a value-object that shouldn\'t be mocked at all. '
            .'If you need to extend a standalone class, create an interface and use the Composite pattern. '
            .'If you aren\'t ready yet for serious OOP, go with FinalInternalClassFixer, it\'s fine.',
            'Risky when subclassing non-abstract classes.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createProxyFixers()
    {
        $fixer = new FinalInternalClassFixer();
        $fixer->configure([
            'annotation-white-list' => [],
            'consider-absent-docblock-as-internal-class' => true,
        ]);

        return [$fixer];
    }
}
