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

final class Line
{
    const ADDED     = 1;
    const REMOVED   = 2;
    const UNCHANGED = 3;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $content;

    public function __construct($type = self::UNCHANGED, $content = '')
    {
        $this->type    = $type;
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getType()
    {
        return $this->type;
    }
}
