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

namespace PhpCsFixer;

use PhpCsFixer\Event\Event;

/**
 * Event that is fired when file was processed by Fixer.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class FixerFileProcessedEvent extends Event
{
    /**
     * Event name.
     */
    const NAME = 'fixer.file_processed';

    const STATUS_UNKNOWN = 0;
    const STATUS_INVALID = 1;
    const STATUS_SKIPPED = 2;
    const STATUS_NO_CHANGES = 3;
    const STATUS_FIXED = 4;
    const STATUS_EXCEPTION = 5;
    const STATUS_LINT = 6;

    /**
     * @var int
     */
    private $status;

    /**
     * @param int $status
     */
    public function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}
