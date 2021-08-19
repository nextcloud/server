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
 * Block
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 *
 * @internal
 */
class Block
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var \ScssPhp\ScssPhp\Block
     */
    public $parent;

    /**
     * @var string
     */
    public $sourceName;

    /**
     * @var integer
     */
    public $sourceIndex;

    /**
     * @var integer
     */
    public $sourceLine;

    /**
     * @var integer
     */
    public $sourceColumn;

    /**
     * @var array|null
     */
    public $selectors;

    /**
     * @var array
     */
    public $comments;

    /**
     * @var array
     */
    public $children;

    /**
     * @var \ScssPhp\ScssPhp\Block|null
     */
    public $selfParent;
}
