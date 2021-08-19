<?php

namespace OpenStack\Test\Common\Transport;

use OpenStack\Common\Api\Operation;
use OpenStack\Common\Api\Parameter;
use OpenStack\Common\Transport\JsonSerializer;
use OpenStack\Common\Transport\RequestSerializer;
use OpenStack\Test\TestCase;

class RequestSerializerTest extends TestCase
{
    private $rs;
    private $js;

    public function setUp(): void
    {
        $this->js = $this->prophesize(JsonSerializer::class);

        $this->rs = new RequestSerializer($this->js->reveal());
    }

    public function test_it_ignores_undefined_params()
    {
        $op = $this->prophesize(Operation::class);
        $op->getParam('foo')->shouldBeCalled()->willReturn(null);

        self::assertEquals(['headers' => []], $this->rs->serializeOptions($op->reveal(), ['foo' => 'bar']));
    }

    public function test_it_serializes_queries()
    {
        $sch = $this->prophesize(Parameter::class);
        $sch->getName()->shouldBeCalled()->willReturn('fooAlias');
        $sch->getLocation()->shouldBeCalled()->willReturn('query');

        $op = $this->prophesize(Operation::class);
        $op->getParam('foo')->shouldBeCalled()->willReturn($sch);

        $actual = $this->rs->serializeOptions($op->reveal(), ['foo' => 'bar']);
        $expected = ['query' => ['fooAlias' => 'bar'], 'headers' => []];

        self::assertEquals($expected, $actual);
    }

    public function test_it_serializes_headers()
    {
        $sch = $this->prophesize(Parameter::class);
        $sch->getLocation()->shouldBeCalled()->willReturn('header');
        $sch->getName()->shouldBeCalled()->willReturn('fooAlias');
        $sch->getPrefixedName()->shouldBeCalled()->willReturn('prefix-fooAlias');

        $op = $this->prophesize(Operation::class);
        $op->getParam('foo')->shouldBeCalled()->willReturn($sch);

        $actual = $this->rs->serializeOptions($op->reveal(), ['foo' => 'bar']);
        $expected = ['headers' => ['prefix-fooAlias' => 'bar']];

        self::assertEquals($expected, $actual);
    }

    public function test_it_serializes_metadata_headers()
    {
        $itemSch = $this->prophesize(Parameter::class);
        $itemSch->getName()->shouldBeCalled()->willReturn('foo');
        $itemSch->getPrefixedName()->shouldBeCalled()->willReturn('prefix-foo');

        $sch = $this->prophesize(Parameter::class);
        $sch->getItemSchema()->shouldBeCalled()->willReturn($itemSch);
        $sch->getLocation()->shouldBeCalled()->willReturn('header');
        $sch->getName()->shouldBeCalled()->willReturn('metadata');

        $op = $this->prophesize(Operation::class);
        $op->getParam('metadata')->shouldBeCalled()->willReturn($sch);

        $actual = $this->rs->serializeOptions($op->reveal(), ['metadata' => ['foo' => 'bar']]);
        $expected = ['headers' => ['prefix-foo' => 'bar']];

        self::assertEquals($expected, $actual);
    }

    public function test_it_serializes_json()
    {
        $sch = $this->prophesize(Parameter::class);
        $sch->getLocation()->shouldBeCalled()->willReturn('json');

        $op = $this->prophesize(Operation::class);
        $op->getParam('foo')->shouldBeCalled()->willReturn($sch);
        $op->getJsonKey()->shouldBeCalled()->willReturn('jsonKey');

        $this->js->stockJson($sch, 'bar', [])->shouldBeCalled()->willReturn(['foo' => 'bar']);

        $actual = $this->rs->serializeOptions($op->reveal(), ['foo' => 'bar']);
        $expected = ['json' => ['jsonKey' => ['foo' => 'bar']], 'headers' => []];

        self::assertEquals($expected, $actual);
    }

    public function test_it_serializes_unescaped_json()
    {
        $sch = $this->prophesize(Parameter::class);
        $sch->getLocation()->shouldBeCalled()->willReturn('json');

        $op = $this->prophesize(Operation::class);
        $op->getParam('foo')->shouldBeCalled()->willReturn($sch);
        $op->getJsonKey()->shouldBeCalled()->willReturn('');

        $this->js->stockJson($sch, 'bar/baz', [])->shouldBeCalled()->willReturn(['foo' => 'bar/baz']);

        $actual = $this->rs->serializeOptions($op->reveal(), ['foo' => 'bar/baz']);
        $expected = ['body' => '{"foo":"bar/baz"}', 'headers' => ['Content-Type' => 'application/json']];

        self::assertEquals($expected, $actual);
    }

    public function test_it_serializes_raw_vals()
    {
        $sch = $this->prophesize(Parameter::class);
        $sch->getLocation()->shouldBeCalled()->willReturn('raw');

        $op = $this->prophesize(Operation::class);
        $op->getParam('foo')->shouldBeCalled()->willReturn($sch);

        $actual = $this->rs->serializeOptions($op->reveal(), ['foo' => 'bar']);
        $expected = ['body' => 'bar', 'headers' => []];

        self::assertEquals($expected, $actual);
    }

    public function test_it_does_serialize_unknown_locations()
    {
        $sch = $this->prophesize(Parameter::class);
        $sch->getLocation()->shouldBeCalled()->willReturn('foo');

        $op = $this->prophesize(Operation::class);
        $op->getParam('foo')->shouldBeCalled()->willReturn($sch);

        $actual = $this->rs->serializeOptions($op->reveal(), ['foo' => 'bar']);
        $expected = ['headers' => []];

        self::assertEquals($expected, $actual);
    }
}
