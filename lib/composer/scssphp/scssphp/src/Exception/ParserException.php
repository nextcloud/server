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

namespace ScssPhp\ScssPhp\Exception;

/**
 * Parser Exception
 *
 * @author Oleksandr Savchenko <traveltino@gmail.com>
 *
 * @internal
 */
class ParserException extends \Exception implements SassException
{
    /**
     * @var array
     */
    private $sourcePosition;

    /**
     * Get source position
     *
     * @api
     */
    public function getSourcePosition()
    {
        return $this->sourcePosition;
    }

    /**
     * Set source position
     *
     * @api
     *
     * @param array $sourcePosition
     */
    public function setSourcePosition($sourcePosition)
    {
        $this->sourcePosition = $sourcePosition;
    }
}
