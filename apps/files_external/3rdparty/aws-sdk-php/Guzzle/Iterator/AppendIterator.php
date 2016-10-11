<?php

namespace Guzzle\Iterator;

/**
 * AppendIterator that is not affected by https://bugs.php.net/bug.php?id=49104
 */
class AppendIterator extends \AppendIterator
{
    /**
     * Works around the bug in which PHP calls rewind() and next() when appending
     *
     * @param \Iterator $iterator Iterator to append
     */
    public function append(\Iterator $iterator)
    {
        $this->getArrayIterator()->append($iterator);
    }
}
