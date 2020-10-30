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

namespace PhpCsFixer\Linter;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class TokenizerLintingResult implements LintingResultInterface
{
    /**
     * @var null|\ParseError
     */
    private $error;

    public function __construct(\ParseError $error = null)
    {
        $this->error = $error;
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        if (null !== $this->error) {
            throw new LintingException(
                sprintf('PHP Parse error: %s on line %d.', $this->error->getMessage(), $this->error->getLine()),
                $this->error->getCode(),
                $this->error
            );
        }
    }
}
