<?php

namespace OpenStack\Test\Common;

use OpenStack\Common\ArrayAccessTrait;
use OpenStack\Test\TestCase;

class ArrayAccessTraitTest extends TestCase
{
    private $aa;

    public function setUp(): void
    {
        $this->aa = new ArrayAccess();
    }

    public function test_offset_is_set()
    {
        $this->aa->offsetSet('foo', 'bar');
        self::assertEquals(['foo' => 'bar'], $this->aa->getElements());
    }

    public function test_it_appends_if_no_key_is_set()
    {
        $this->aa->offsetSet(null, 'bar');
        self::assertEquals(['bar'], $this->aa->getElements());
    }

    public function test_if_checks_if_offset_exists()
    {
        $this->aa->offsetSet('bar', 'foo');
        self::assertTrue($this->aa->offsetExists('bar'));
        self::assertFalse($this->aa->offsetExists('baz'));
    }

    public function test_if_gets_offset()
    {
        $this->aa->offsetSet('bar', 'foo');
        self::assertEquals('foo', $this->aa->offsetGet('bar'));
        self::assertNull($this->aa->offsetGet('baz'));
    }

    public function test_it_unsets_offset()
    {
        $this->aa->offsetSet('bar', 'foo');
        $this->aa->offsetUnset('bar');
        self::assertNull($this->aa->offsetGet('bar'));
    }
}

class ArrayAccess
{
    use ArrayAccessTrait;

    public function getElements()
    {
        return $this->internalState;
    }
}
