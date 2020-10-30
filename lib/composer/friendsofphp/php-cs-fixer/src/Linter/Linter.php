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
 * Handle PHP code linting process.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class Linter implements LinterInterface
{
    /**
     * @var LinterInterface
     */
    private $sublinter;

    /**
     * @param null|string $executable PHP executable, null for autodetection
     */
    public function __construct($executable = null)
    {
        try {
            $this->sublinter = new TokenizerLinter();
        } catch (UnavailableLinterException $e) {
            $this->sublinter = new ProcessLinter($executable);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAsync()
    {
        return $this->sublinter->isAsync();
    }

    /**
     * {@inheritdoc}
     */
    public function lintFile($path)
    {
        return $this->sublinter->lintFile($path);
    }

    /**
     * {@inheritdoc}
     */
    public function lintSource($source)
    {
        return $this->sublinter->lintSource($source);
    }
}
