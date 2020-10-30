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

namespace phpDocumentor\Reflection\Types;

use phpDocumentor\Reflection\Type;

/**
 * Value Object representing the '$this' pseudo-type.
 *
 * $this, as a Type, represents the instance of the class associated with the element as it was called. $this is
 * commonly used when documenting fluent interfaces since it represents that the same object is returned.
 *
 * @psalm-immutable
 */
final class This implements Type
{
    /**
     * Returns a rendered output of the Type as it would be used in a DocBlock.
     */
    public function __toString() : string
    {
        return '$this';
    }
}
