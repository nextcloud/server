<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Processor;

/**
 * Some methods that are common for all memory processors
 *
 * @author Rob Jensen
 */
abstract class MemoryProcessor
{
    protected $realUsage;

    /**
     * @param boolean $realUsage
     */
    public function __construct($realUsage = true)
    {
        $this->realUsage = (boolean) $realUsage;
    }

    /**
     * Formats bytes into a human readable string
     *
     * @param  int    $bytes
     * @return string
     */
    protected static function formatBytes($bytes)
    {
        $bytes = (int) $bytes;

        if ($bytes > 1024*1024) {
            return round($bytes/1024/1024, 2).' MB';
        } elseif ($bytes > 1024) {
            return round($bytes/1024, 2).' KB';
        }

        return $bytes . ' B';
    }

}
