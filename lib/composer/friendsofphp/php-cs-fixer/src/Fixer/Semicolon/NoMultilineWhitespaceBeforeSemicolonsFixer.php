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

namespace PhpCsFixer\Fixer\Semicolon;

use PhpCsFixer\AbstractProxyFixer;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;

/**
 * @deprecated since 2.9.1, replaced by MultilineWhitespaceBeforeSemicolonsFixer
 *
 * @todo To be removed at 3.0
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
final class NoMultilineWhitespaceBeforeSemicolonsFixer extends AbstractProxyFixer implements DeprecatedFixerInterface, WhitespacesAwareFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Multi-line whitespace before closing semicolon are prohibited.',
            [
                new CodeSample(
                    '<?php
function foo () {
    return 1 + 2
        ;
}
'
                ),
            ]
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
        $fixer = new MultilineWhitespaceBeforeSemicolonsFixer();
        $fixer->configure(['strategy' => MultilineWhitespaceBeforeSemicolonsFixer::STRATEGY_NO_MULTI_LINE]);

        return [$fixer];
    }
}
