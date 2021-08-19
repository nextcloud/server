<?php

namespace OpenStack\Test\Common\Transport;

use Guzzle\Tests\Service\Mock\Command\Sub\Sub;
use OpenStack\Common\Api\Parameter;
use OpenStack\Common\Resource\AbstractResource;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Transport\JsonSerializer;

class JsonSerializerTest extends \PHPUnit\Framework\TestCase
{
    /** @var JsonSerializer */
    private $serializer;

    public function setUp(): void
    {
        $this->serializer = new JsonSerializer();
    }

    public function test_it_embeds_params_according_to_path()
    {
        $param = $this->prophesize(Parameter::class);
        $param->isArray()->shouldBeCalled()->willReturn(false);
        $param->isObject()->shouldBeCalled()->willReturn(false);
        $param->getName()->shouldBeCalled()->willReturn('username');
        $param->getPath()->shouldBeCalled()->willReturn('auth.passwordCredentials');

        $userValue = 'fooBar';

        $expected = [
            'auth' => [
                'passwordCredentials' => [
                    'username' => $userValue,
                ],
            ],
        ];

        $actual = $this->serializer->stockJson($param->reveal(), $userValue, []);

        self::assertEquals($expected, $actual);
    }

    public function test_it_serializes_arrays()
    {
        $param = $this->prophesize(Parameter::class);
        $param->isArray()->shouldBeCalled()->willReturn(true);
        $param->getName()->shouldBeCalled()->willReturn('fooBar');
        $param->getPath()->shouldBeCalled()->willReturn(false);

        $itemSchema = $this->prophesize(Parameter::class);
        $itemSchema->isArray()->shouldBeCalled()->willReturn(false);
        $itemSchema->isObject()->shouldBeCalled()->willReturn(false);
        $itemSchema->getName()->shouldBeCalled()->willReturn('');
        $itemSchema->getPath()->shouldBeCalled()->willReturn('');

        $param->getItemSchema()->shouldBeCalled()->willReturn($itemSchema);

        $userValues = ['1', '2', '3'];

        $expected = ['fooBar' => $userValues];

        $actual = $this->serializer->stockJson($param->reveal(), $userValues, []);

        self::assertEquals($expected, $actual);
    }

    public function test_it_serializes_objects()
    {
        $prop = $this->prophesize(Parameter::class);
        $prop->isArray()->shouldBeCalled()->willReturn(false);
        $prop->isObject()->shouldBeCalled()->willReturn(false);
        $prop->getName()->shouldBeCalled()->willReturn('foo');
        $prop->getPath()->shouldBeCalled()->willReturn('');

        $param = $this->prophesize(Parameter::class);
        $param->isArray()->shouldBeCalled()->willReturn(false);
        $param->isObject()->shouldBeCalled()->willReturn(true);
        $param->getName()->shouldBeCalled()->willReturn('topLevel');
        $param->getPath()->shouldBeCalled()->willReturn(false);
        $param->getProperty('foo')->shouldBeCalled()->willReturn($prop);

        $expected = ['topLevel' => ['foo' => true]];

        $json = $this->serializer->stockJson($param->reveal(), (object)['foo' => true], []);

        self::assertEquals($expected, $json);
    }

    public function test_it_serializes_non_stdClass_objects()
    {
        $prop1 = $this->prophesize(Parameter::class);
        $prop1->isArray()->shouldBeCalled()->willReturn(false);
        $prop1->isObject()->shouldBeCalled()->willReturn(false);
        $prop1->getName()->shouldBeCalled()->willReturn('id');
        $prop1->getPath()->shouldBeCalled()->willReturn('');

        $prop2 = $this->prophesize(Parameter::class);
        $prop2->isArray()->shouldBeCalled()->willReturn(false);
        $prop2->isObject()->shouldBeCalled()->willReturn(false);
        $prop2->getName()->shouldBeCalled()->willReturn('foo_name');
        $prop2->getPath()->shouldBeCalled()->willReturn('');

        $prop3 = $this->prophesize(Parameter::class);
        $prop3->isArray()->shouldBeCalled()->willReturn(false);
        $prop3->isObject()->shouldBeCalled()->willReturn(false);
        $prop3->getName()->shouldBeCalled()->willReturn('created_date');
        $prop3->getPath()->shouldBeCalled()->willReturn('');

        $subParam = $this->prophesize(Parameter::class);
        $subParam->isArray()->shouldBeCalled()->willReturn(false);
        $subParam->isObject()->shouldBeCalled()->willReturn(true);
        $subParam->getProperty('id')->shouldBeCalled()->willReturn($prop1);
        $subParam->getProperty('fooName')->shouldBeCalled()->willReturn($prop2);
        $subParam->getProperty('createdDate')->shouldBeCalled()->willReturn($prop3);
        $subParam->getName()->shouldBeCalled()->willReturn('sub_resource');
        $subParam->getPath()->shouldBeCalled()->willReturn('');

        $param = $this->prophesize(Parameter::class);
        $param->isArray()->shouldBeCalled()->willReturn(false);
        $param->isObject()->shouldBeCalled()->willReturn(true);
        $param->getProperty('subResource')->shouldBeCalled()->willReturn($subParam);
        $param->getName()->shouldBeCalled()->willReturn('resource');
        $param->getPath()->shouldBeCalled()->willReturn('');

        $subResource = new SubResource();
        $subResource->id = 1;
        $subResource->fooName = 2;
        $subResource->createdDate = 3;

        $userValues = ['subResource' => $subResource];

        $json = $this->serializer->stockJson($param->reveal(), $userValues, []);

        $expected = [
            'resource' => [
                'sub_resource' => [
                    'id'           => 1,
                    'foo_name'     => 2,
                    'created_date' => 3,
                ],
            ],
        ];

        self::assertEquals($expected, $json);
    }

    public function test_exception_is_thrown_when_non_stdClass_or_serializable_object_provided()
    {
        $subParam = $this->prophesize(Parameter::class);
        $subParam->isArray()->shouldBeCalled()->willReturn(false);
        $subParam->isObject()->shouldBeCalled()->willReturn(true);

        $param = $this->prophesize(Parameter::class);
        $param->isArray()->shouldBeCalled()->willReturn(false);
        $param->isObject()->shouldBeCalled()->willReturn(true);
        $param->getProperty('subResource')->shouldBeCalled()->willReturn($subParam);

        $userValues = ['subResource' => new NonSerializableResource()];
		$this->expectException(\InvalidArgumentException::class);

        $this->serializer->stockJson($param->reveal(), $userValues, []);
    }
}

class TestResource extends AbstractResource
{
    /** @var SubResource */
    public $subResource;
}

class SubResource extends AbstractResource
{
    /** @var int */
    public $id;

    /** @var int */
    public $fooName;

    /** @var int */
    public $createdDate;
}

class NonSerializableResource
{
}
