<?php

/*
 * This file is part of the webmozart/glob package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Glob\Tests\Iterator;

use ArrayIterator;
use PHPUnit_Framework_TestCase;
use Webmozart\Glob\Iterator\RegexFilterIterator;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class RegexFilterIteratorTest extends PHPUnit_Framework_TestCase
{
    public function testIterate()
    {
        $values = array(
            '/foo',
            '/foo/bar',
            '/foo/bar/baz',
            '/foo/baz',
            '/bar',
        );

        $expected = array(
            '/foo',
            '/foo/bar',
            '/foo/baz',
        );

        $iterator = new RegexFilterIterator(
            '~^/foo(/[^/]+)?$~',
            '/foo',
            new ArrayIterator($values)
        );

        $this->assertSame($expected, iterator_to_array($iterator));

        $this->assertFalse($iterator->valid());
        $this->assertNull($iterator->key());
        $this->assertNull($iterator->current());
    }

    public function testIterateTwice()
    {
        $values = array(
            '/foo',
            '/foo/bar',
            '/foo/bar/baz',
            '/foo/baz',
            '/bar',
        );

        $expected = array(
            '/foo',
            '/foo/bar',
            '/foo/baz',
        );

        $iterator = new RegexFilterIterator(
            '~^/foo(/[^/]+)?$~',
            '/foo',
            new ArrayIterator($values)
        );

        // Make sure everything is rewound correctly
        $this->assertSame($expected, iterator_to_array($iterator));
        $this->assertSame($expected, iterator_to_array($iterator));

        $this->assertFalse($iterator->valid());
        $this->assertNull($iterator->key());
        $this->assertNull($iterator->current());
    }

    public function testIterateKeyAsKey()
    {
        $values = array(
            'a' => '/foo',
            'b' => '/foo/bar',
            'c' => '/foo/bar/baz',
            'd' => '/foo/baz',
            'e' => '/bar',
        );

        $expected = array(
            'a' => '/foo',
            'b' => '/foo/bar',
            'd' => '/foo/baz',
        );

        $iterator = new RegexFilterIterator(
            '~^/foo(/[^/]+)?$~',
            '/foo',
            new ArrayIterator($values),
            RegexFilterIterator::KEY_AS_KEY
        );

        $this->assertSame($expected, iterator_to_array($iterator));

        $this->assertFalse($iterator->valid());
        $this->assertNull($iterator->key());
        $this->assertNull($iterator->current());
    }
}
