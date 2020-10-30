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

namespace PhpCsFixer\Fixer\Operator;

use PhpCsFixer\AbstractProxyFixer;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;

/**
 * @author Gregor Harlan <gharlan@web.de>
 *
 * @deprecated in 2.8, proxy to IncrementStyleFixer
 */
final class PreIncrementFixer extends AbstractProxyFixer implements DeprecatedFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Pre incrementation/decrementation should be used if possible.',
            [new CodeSample("<?php\n\$a++;\n\$b--;\n")]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSuccessorsNames()
    {
        return array_keys($this->proxyFixers);
    }

    /**
     * {@inheritdoc}
     */
    protected function createProxyFixers()
    {
        $fixer = new IncrementStyleFixer();
        $fixer->configure(['style' => 'pre']);

        return [$fixer];
    }
}
