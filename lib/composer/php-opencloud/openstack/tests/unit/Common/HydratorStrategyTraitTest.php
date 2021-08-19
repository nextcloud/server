<?php

namespace OpenStack\Test\Common;

use OpenStack\Common\HydratorStrategyTrait;
use OpenStack\Test\TestCase;

class HydratorStrategyTraitTest extends TestCase
{
    /** @var Fixture */
    private $fixture;

    public function setUp(): void
    {
        $this->fixture = new Fixture();
    }

    public function test_it_hydrates()
    {
        $data = ['foo' => 1, 'bar' => 2, 'baz' => 3, 'boo' => 4];

        $this->fixture->hydrate($data);

        self::assertEquals(1, $this->fixture->foo);
        self::assertEquals(2, $this->fixture->getBar());
        self::assertEquals(3, $this->fixture->getBaz());
    }

    public function test_it_hydrates_aliases()
    {
        $this->fixture->hydrate(['FOO!' => 1], ['FOO!' => 'foo']);

        self::assertEquals(1, $this->fixture->foo);
    }

    public function test_it_sets()
    {
        $data = ['foo1' => 1];

        $this->fixture->set('foo1', 'foo', $data);
        self::assertEquals(1, $this->fixture->foo);
    }
}

class Fixture
{
    public $foo;
    protected $bar;
    private $baz;

    use HydratorStrategyTrait;

    public function getBar()
    {
        return $this->bar;
    }

    public function getBaz()
    {
        return $this->baz;
    }
}
