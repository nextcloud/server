<?php
/**
 * This file is part of phpDocumentor.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @link      http://phpdoc.org
 */

declare(strict_types=1);

namespace phpDocumentor\Reflection\Types;

use ArrayIterator;
use IteratorAggregate;
use phpDocumentor\Reflection\Type;
use function array_key_exists;
use function implode;

/**
 * Base class for aggregated types like Compound and Intersection
 *
 * A Aggregated Type is not so much a special keyword or object reference but is a series of Types that are separated
 * using separator.
 *
 * @psalm-immutable
 * @template-implements IteratorAggregate<int, Type>
 */
abstract class AggregatedType implements Type, IteratorAggregate
{
    /**
     * @psalm-allow-private-mutation
     * @var array<int, Type>
     */
    private $types = [];

    /** @var string */
    private $token;

    /**
     * @param array<Type> $types
     */
    public function __construct(array $types, string $token)
    {
        foreach ($types as $type) {
            $this->add($type);
        }

        $this->token = $token;
    }

    /**
     * Returns the type at the given index.
     */
    public function get(int $index) : ?Type
    {
        if (!$this->has($index)) {
            return null;
        }

        return $this->types[$index];
    }

    /**
     * Tests if this compound type has a type with the given index.
     */
    public function has(int $index) : bool
    {
        return array_key_exists($index, $this->types);
    }

    /**
     * Tests if this compound type contains the given type.
     */
    public function contains(Type $type) : bool
    {
        foreach ($this->types as $typePart) {
            // if the type is duplicate; do not add it
            if ((string) $typePart === (string) $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a rendered output of the Type as it would be used in a DocBlock.
     */
    public function __toString() : string
    {
        return implode($this->token, $this->types);
    }

    /**
     * @return ArrayIterator<int, Type>
     */
    public function getIterator() : ArrayIterator
    {
        return new ArrayIterator($this->types);
    }

    /**
     * @psalm-suppress ImpureMethodCall
     */
    private function add(Type $type) : void
    {
        if ($type instanceof self) {
            foreach ($type->getIterator() as $subType) {
                $this->add($subType);
            }

            return;
        }

        // if the type is duplicate; do not add it
        if ($this->contains($type)) {
            return;
        }

        $this->types[] = $type;
    }
}
