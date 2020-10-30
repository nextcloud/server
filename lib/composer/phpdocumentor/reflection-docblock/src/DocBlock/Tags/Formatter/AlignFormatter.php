<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Reflection\DocBlock\Tags\Formatter;

use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Formatter;
use function max;
use function str_repeat;
use function strlen;

class AlignFormatter implements Formatter
{
    /** @var int The maximum tag name length. */
    protected $maxLen = 0;

    /**
     * @param Tag[] $tags All tags that should later be aligned with the formatter.
     */
    public function __construct(array $tags)
    {
        foreach ($tags as $tag) {
            $this->maxLen = max($this->maxLen, strlen($tag->getName()));
        }
    }

    /**
     * Formats the given tag to return a simple plain text version.
     */
    public function format(Tag $tag) : string
    {
        return '@' . $tag->getName() .
            str_repeat(
                ' ',
                $this->maxLen - strlen($tag->getName()) + 1
            ) .
            $tag;
    }
}
