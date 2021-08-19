<?php

namespace OpenStack\Test\Images\v2;

use OpenStack\Images\v2\JsonPatch;
use OpenStack\Test\TestCase;

class JsonPatchTest extends TestCase
{
    private $jsonPatch;

    public function setUp(): void
    {
        $this->jsonPatch = new JsonPatch();
    }

    public function test_it_adds_object_properties_if_none_previously_exists()
    {
        $src = (object) [];
        $des = (object) ['foo' => 'val'];

        $actual = $this->jsonPatch->makeDiff($src, $des);
        $expected = [
            ['op' => 'add', 'path' => '/foo', 'value' => 'val'],
        ];

        self::assertEquals($expected, $actual);
    }

    public function test_it_removes_elements_from_arrays()
    {
        $src = (object) ['foo' => [1, 2, 3]];
        $des = (object) ['foo' => [1, 2]];

        $actual = $this->jsonPatch->makeDiff($src, $des);
        $expected = [
            ['op' => 'remove', 'path' => '/foo/2'],
        ];

        self::assertEquals($expected, $actual);
    }
}
