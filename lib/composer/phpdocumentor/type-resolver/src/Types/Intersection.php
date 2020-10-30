<?php
/**
 * This file is part of phpDocumentor.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 *  @link      http://phpdoc.org
 */

declare(strict_types=1);

namespace phpDocumentor\Reflection\Types;

use phpDocumentor\Reflection\Type;

/**
 * Value Object representing a Compound Type.
 *
 * A Intersection Type is not so much a special keyword or object reference but is a series of Types that are separated
 * using an AND operator (`&`). This combination of types signifies that whatever is associated with this Intersection
 * type may contain a value with any of the given types.
 *
 * @psalm-immutable
 */
final class Intersection extends AggregatedType
{
    /**
     * Initializes a intersection type (i.e. `\A&\B`) and tests if the provided types all implement the Type interface.
     *
     * @param array<Type> $types
     */
    public function __construct(array $types)
    {
        parent::__construct($types, '&');
    }
}
