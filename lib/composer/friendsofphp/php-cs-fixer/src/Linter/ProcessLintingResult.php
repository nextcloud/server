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

use Symfony\Component\Process\Process;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class ProcessLintingResult implements LintingResultInterface
{
    /**
     * @var bool
     */
    private $isSuccessful;

    /**
     * @var Process
     */
    private $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        if (!$this->isSuccessful()) {
            // on some systems stderr is used, but on others, it's not
            throw new LintingException($this->process->getErrorOutput() ?: $this->process->getOutput(), $this->process->getExitCode());
        }
    }

    private function isSuccessful()
    {
        if (null === $this->isSuccessful) {
            $this->process->wait();
            $this->isSuccessful = $this->process->isSuccessful();
        }

        return $this->isSuccessful;
    }
}
