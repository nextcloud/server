<?php

/**
 * This file is part of the ramsey/collection library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Collection;

/**
 * A collection represents a group of objects.
 *
 * Each object in the collection is of a specific, defined type.
 *
 * This is a direct implementation of `CollectionInterface`, provided for
 * the sake of convenience.
 *
 * Example usage:
 *
 * ``` php
 * $collection = new \Ramsey\Collection\Collection('My\\Foo');
 * $collection->add(new \My\Foo());
 * $collection->add(new \My\Foo());
 *
 * foreach ($collection as $foo) {
 *     // Do something with $foo
 * }
 * ```
 *
 * It is preferable to subclass `AbstractCollection` to create your own typed
 * collections. For example:
 *
 * ``` php
 * namespace My\Foo;
 *
 * class FooCollection extends \Ramsey\Collection\AbstractCollection
 * {
 *     public function getType()
 *     {
 *         return 'My\\Foo';
 *     }
 * }
 * ```
 *
 * And then use it similarly to the earlier example:
 *
 * ``` php
 * $fooCollection = new \My\Foo\FooCollection();
 * $fooCollection->add(new \My\Foo());
 * $fooCollection->add(new \My\Foo());
 *
 * foreach ($fooCollection as $foo) {
 *     // Do something with $foo
 * }
 * ```
 *
 * The benefit with this approach is that you may do type-checking on the
 * collection object:
 *
 * ``` php
 * if ($collection instanceof \My\Foo\FooCollection) {
 *     // the collection is a collection of My\Foo objects
 * }
 * ```
 *
 * @template T
 * @extends AbstractCollection<T>
 */
class Collection extends AbstractCollection
{
    /**
     * The type of elements stored in this collection.
     *
     * A collection's type is immutable once it is set. For this reason, this
     * property is set private.
     *
     * @var string
     */
    private $collectionType;

    /**
     * Constructs a collection object of the specified type, optionally with the
     * specified data.
     *
     * @param string $collectionType The type (FQCN) associated with this
     *     collection.
     * @param array<array-key, T> $data The initial items to store in the collection.
     */
    public function __construct(string $collectionType, array $data = [])
    {
        $this->collectionType = $collectionType;
        parent::__construct($data);
    }

    public function getType(): string
    {
        return $this->collectionType;
    }
}
