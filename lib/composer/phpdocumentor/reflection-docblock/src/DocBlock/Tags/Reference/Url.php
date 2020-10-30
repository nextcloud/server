<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @link http://phpdoc.org
 */

namespace phpDocumentor\Reflection\DocBlock\Tags\Reference;

use Webmozart\Assert\Assert;

/**
 * Url reference used by {@see \phpDocumentor\Reflection\DocBlock\Tags\See}
 */
final class Url implements Reference
{
    /** @var string */
    private $uri;

    public function __construct(string $uri)
    {
        Assert::stringNotEmpty($uri);
        $this->uri = $uri;
    }

    public function __toString() : string
    {
        return $this->uri;
    }
}
