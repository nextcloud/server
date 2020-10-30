<?php
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpCsFixer\Diff\v3_0;

final class Diff
{
    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $to;

    /**
     * @var Chunk[]
     */
    private $chunks;

    /**
     * @param string  $from
     * @param string  $to
     * @param Chunk[] $chunks
     */
    public function __construct($from, $to, array $chunks = [])
    {
        $this->from   = $from;
        $this->to     = $to;
        $this->chunks = $chunks;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getTo()
    {
        return $this->to;
    }

    /**
     * @return Chunk[]
     */
    public function getChunks()
    {
        return $this->chunks;
    }

    /**
     * @param Chunk[] $chunks
     */
    public function setChunks(array $chunks)
    {
        $this->chunks = $chunks;
    }
}
