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

namespace PhpCsFixer\Report;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class ReportSummary
{
    /**
     * @var bool
     */
    private $addAppliedFixers;

    /**
     * @var array
     */
    private $changed;

    /**
     * @var bool
     */
    private $isDecoratedOutput;

    /**
     * @var bool
     */
    private $isDryRun;

    /**
     * @var int
     */
    private $memory;

    /**
     * @var int
     */
    private $time;

    /**
     * @param int  $time              duration in milliseconds
     * @param int  $memory            memory usage in bytes
     * @param bool $addAppliedFixers
     * @param bool $isDryRun
     * @param bool $isDecoratedOutput
     */
    public function __construct(
        array $changed,
        $time,
        $memory,
        $addAppliedFixers,
        $isDryRun,
        $isDecoratedOutput
    ) {
        $this->changed = $changed;
        $this->time = $time;
        $this->memory = $memory;
        $this->addAppliedFixers = $addAppliedFixers;
        $this->isDryRun = $isDryRun;
        $this->isDecoratedOutput = $isDecoratedOutput;
    }

    /**
     * @return bool
     */
    public function isDecoratedOutput()
    {
        return $this->isDecoratedOutput;
    }

    /**
     * @return bool
     */
    public function isDryRun()
    {
        return $this->isDryRun;
    }

    /**
     * @return array
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @return int
     */
    public function getMemory()
    {
        return $this->memory;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return bool
     */
    public function shouldAddAppliedFixers()
    {
        return $this->addAppliedFixers;
    }
}
