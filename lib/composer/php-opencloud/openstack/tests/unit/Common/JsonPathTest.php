<?php

namespace OpenStack\Test\Common;

use OpenStack\Common\JsonPath;

class JsonPathTest extends \PHPUnit\Framework\TestCase
{
    private $jsonPath;

    public function setUp(): void
    {
        $this->jsonPath = new JsonPath([]);
    }

    public function test_it_sets_values_according_to_paths()
    {
        $this->jsonPath->set('foo.bar.baz', 'VALUE');

        $expected = [
            'foo' => [
                'bar' => [
                    'baz' => 'VALUE',
                ]
            ]
        ];

        self::assertEquals($expected, $this->jsonPath->getStructure());
    }

    public function test_it_sets_arrays_according_to_paths()
    {
        $jsonPath = new JsonPath([
            'foo' => [
                'bar' => [
                    'value' => 'VALUE',
                ]
            ]
        ]);

        $jsonPath->set('foo.bar.items', ['item_1', 'item_2']);

        $expected = [
            'foo' => [
                'bar' => [
                    'value' => 'VALUE',
                    'items' => ['item_1', 'item_2'],
                ]
            ]
        ];

        self::assertEquals($expected, $jsonPath->getStructure());
    }

    public function test_it_gets_values_according_to_paths()
    {
        $jsonPath = new JsonPath([
            'foo' => [
                'bar' => [
                    'baz' => 'VALUE_1',
                    'lol' => 'VALUE_2',
                ]
            ]
        ]);

        self::assertEquals('VALUE_1', $jsonPath->get('foo.bar.baz'));
        self::assertEquals('VALUE_2', $jsonPath->get('foo.bar.lol'));
        self::assertNull($jsonPath->get('foo.bar.boo'));
    }
}
