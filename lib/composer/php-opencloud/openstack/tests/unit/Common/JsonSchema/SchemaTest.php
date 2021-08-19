<?php

namespace OpenStack\Test\Common\JsonSchema;

use JsonSchema\Validator;
use OpenStack\Common\JsonSchema\Schema;
use OpenStack\Test\TestCase;

class SchemaTest extends TestCase
{
    /** @var Schema */
    private $schema;

    /** @var Validator */
    private $validator;
    private $body;

    public function setUp(): void
    {
        $this->body = [
            'properties' => [
                'foo' => (object)[],
                'bar' => (object)[],
                'baz' => (object)['readOnly' => true],
            ],
        ];

        $this->validator = $this->prophesize(Validator::class);
        $this->schema = new Schema($this->body, $this->validator->reveal());
    }

    public function test_it_gets_errors()
    {
        $this->validator->getErrors()
            ->shouldBeCalled()
            ->willReturn([]);

        self::assertEquals([], $this->schema->getErrors());
    }

    public function test_it_gets_error_string()
    {
        $this->validator->getErrors()
            ->shouldBeCalled()
            ->willReturn([['property' => 'foo', 'message' => 'bar']]);

        $errorMsg = sprintf("Provided values do not validate. Errors:\n[foo] bar\n");

        self::assertEquals($errorMsg, $this->schema->getErrorString());
    }

    public function test_it_gets_property_paths()
    {
        self::assertEquals(['/foo', '/bar', '/baz'], $this->schema->getPropertyPaths());
    }

    public function test_it_ignores_readOnly_attrs()
    {
        $expected = (object)[
            'foo' => true,
            'bar' => false,
        ];

        $subject = (object)[
            'foo' => true,
            'bar' => false,
            'baz' => true,
        ];

        self::assertEquals((object)$expected, $this->schema->normalizeObject((object)$subject, []));
    }

    public function test_it_stocks_aliases()
    {
        $subject = (object)[
            'fooAlias' => true,
            'bar'      => false,
            'other'    => true,
        ];

        $expected = (object)[
            'foo' => true,
            'bar' => false,
        ];

        self::assertEquals($expected, $this->schema->normalizeObject($subject, ['foo' => 'fooAlias', 'bar' => 'lol']));
    }

    public function test_it_validates()
    {
        $this->validator->check([], (object) $this->body)->shouldBeCalled();

        $this->schema->validate([]);
    }

    public function test_it_checks_validity()
    {
        $this->validator->isValid()->shouldBeCalled()->willReturn(true);

        $this->schema->isValid();
    }
}
