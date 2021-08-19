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

namespace ScssPhp\ScssPhp;

/**
 * Base node
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 *
 * @internal
 */
abstract class Node
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var integer
     */
    public $sourceIndex;

    /**
     * @var int|null
     */
    public $sourceLine;

    /**
     * @var int|null
     */
    public $sourceColumn;
}
