<?php

namespace OpenStack\Test\Compute\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Compute\v2\Api;
use OpenStack\Compute\v2\Models\Image;
use OpenStack\Test\TestCase;

class ImageTest extends TestCase
{
    private $image;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->image = new Image($this->client->reveal(), new Api());
        $this->image->id = 'imageId';
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'images/imageId', null, [], 'image-get');

        $this->image->retrieve();

        $metadata = [
            "architecture" => "x86_64",
            "auto_disk_config" => "True",
            "kernel_id" => "nokernel",
            "ramdisk_id" => "nokernel"
        ];

        self::assertEquals(new \DateTimeImmutable('2011-01-01T01:02:03Z'), $this->image->created);
        self::assertEquals($metadata, $this->image->metadata);
        self::assertEquals(0, $this->image->minDisk);
        self::assertEquals(0, $this->image->minRam);
        self::assertEquals('fakeimage7', $this->image->name);
        self::assertEquals(100, $this->image->progress);
        self::assertEquals('ACTIVE', $this->image->status);
        self::assertEquals(new \DateTimeImmutable('2011-01-01T01:02:03Z'), $this->image->updated);
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'images/imageId', null, [], new Response(204));

        $this->image->delete();
    }

    public function test_it_retrieves_metadata()
    {
        $this->setupMock('GET', 'images/imageId/metadata', null, [], 'server-metadata-get');

        $metadata = $this->image->getMetadata();

        self::assertEquals('x86_64', $metadata['architecture']);
        self::assertEquals('True', $metadata['auto_disk_config']);
        self::assertEquals('nokernel', $metadata['kernel_id']);
        self::assertEquals('nokernel', $metadata['ramdisk_id']);
    }

    public function test_it_sets_metadata()
    {
        $metadata = ['foo' => '1', 'bar' => '2'];

        $expectedJson = ['metadata' => $metadata];

        $response = $this->createResponse(200, [], $expectedJson);
        $this->setupMock('PUT', 'images/imageId/metadata', $expectedJson, [], $response);

        $this->image->resetMetadata($metadata);

        self::assertEquals('1', $this->image->metadata['foo']);
    }

    public function test_it_updates_metadata()
    {
        $metadata = ['foo' => '1'];

        $expectedJson = ['metadata' => $metadata];

        $response = $this->createResponse(200, [], array_merge_recursive($expectedJson, ['metadata' => ['bar' => '2']]));
        $this->setupMock('POST', 'images/imageId/metadata', $expectedJson, [], $response);

        $this->image->mergeMetadata($metadata);

        self::assertEquals('1', $this->image->metadata['foo']);
        self::assertEquals('2', $this->image->metadata['bar']);
    }

    public function test_it_retrieves_a_metadata_item()
    {
        $response = $this->createResponse(200, [], ['metadata' => ['fooKey' => 'bar']]);
        $this->setupMock('GET', 'images/imageId/metadata/fooKey', null, [], $response);

        $value = $this->image->getMetadataItem('fooKey');

        self::assertEquals('bar', $value);
    }

    public function test_it_deletes_a_metadata_item()
    {
        $this->setupMock('DELETE', 'images/imageId/metadata/fooKey', null, [], new Response(204));

        self::assertNull($this->image->deleteMetadataItem('fooKey'));
    }
}
