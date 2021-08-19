<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Formatter;

/**
 * Output block
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 *
 * @internal
 */
class OutputBlock
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var integer
     */
    public $depth;

    /**
     * @var array|null
     */
    public $selectors;

    /**
     * @var string[]
     */
    public $lines;

    /**
     * @var OutputBlock[]
     */
    public $children;

    /**
     * @var OutputBlock|null
     */
    public $parent;

    /**
     * @var string|null
     */
    public $sourceName;

    /**
     * @var integer|null
     */
    public $sourceLine;

    /**
     * @var integer|null
     */
    public $sourceColumn;
}
