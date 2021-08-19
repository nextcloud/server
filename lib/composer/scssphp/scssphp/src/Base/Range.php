<?php

/**
 * SCSSPHP
 *
 * @copyright 2015-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Base;

/**
 * Range
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 *
 * @internal
 */
class Range
{
    /**
     * @var float|int
     */
    public $first;

    /**
     * @var float|int
     */
    public $last;

    /**
     * Initialize range
     *
     * @param integer|float $first
     * @param integer|float $last
     */
    public function __construct($first, $last)
    {
        $this->first = $first;
        $this->last = $last;
    }

    /**
     * Test for inclusion in range
     *
     * @param integer|float $value
     *
     * @return boolean
     */
    public function includes($value)
    {
        return $value >= $this->first && $value <= $this->last;
    }
}
