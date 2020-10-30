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

/**
 * Interface for references in {@see \phpDocumentor\Reflection\DocBlock\Tags\See}
 */
interface Reference
{
    public function __toString() : string;
}
