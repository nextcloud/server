<?php

namespace OpenStack\Test\ObjectStore\v1\Models;

use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use OpenStack\ObjectStore\v1\Api;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use OpenStack\Test\TestCase;

class ObjectTest extends TestCase
{
    const CONTAINER = 'foo';
    const NAME = 'bar';

    private $object;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->object = new StorageObject($this->client->reveal(), new Api());
        $this->object->containerName = self::CONTAINER;
        $this->object->name = self::NAME;
    }

    public function test_It_Creates()
    {
        $objectName = 'foo.txt';

        $headers = [
            'Content-Type'         => 'application/json',
            'Content-Disposition'  => 'attachment; filename=quot.pdf;',
            'Content-Encoding'     => 'gzip',
            'X-Delete-After'       => '500',
            'X-Object-Meta-Author' => 'foo',
            'X-Object-Meta-genre'  => 'bar',
        ];

        $content = json_encode(['foo' => 'bar']);

        $this->setupMock('PUT', self::CONTAINER . '/' . $objectName, $content, $headers, 'Created');

        $this->object->create([
            'name'               => $objectName,
            'content'            => $content,
            'contentType'        => $headers['Content-Type'],
            'contentEncoding'    => $headers['Content-Encoding'],
            'contentDisposition' => $headers['Content-Disposition'],
            'deleteAfter'        => $headers['X-Delete-After'],
            'metadata'           => ['Author' => 'foo', 'genre' => 'bar'],
        ]);
    }

    public function test_Retrieve()
    {
        $this->setupMock('HEAD', self::CONTAINER . '/' . self::NAME, null, [], 'HEAD_Object');

        $this->object->retrieve();
        self::assertNotEmpty($this->object->metadata);
    }

    public function test_Get_Metadata()
    {
        $this->setupMock('HEAD', self::CONTAINER . '/' . self::NAME, null, [], 'HEAD_Object');

        self::assertEquals([
            'Book'         => 'GoodbyeColumbus',
            'Manufacturer' => 'Acme',
        ], $this->object->getMetadata());
    }

    public function test_Merge_Metadata()
    {
        $this->setupMock('HEAD', self::CONTAINER . '/' . self::NAME, null, [], 'HEAD_Object');

        $headers = [
            'X-Object-Meta-Author'       => 'foo',
            'X-Object-Meta-Book'         => 'GoodbyeColumbus',
            'X-Object-Meta-Manufacturer' => 'Acme',
        ];

        $this->setupMock('POST', self::CONTAINER . '/' . self::NAME, null, $headers, 'NoContent');

        $this->object->mergeMetadata(['Author' => 'foo']);
    }

    public function test_Reset_Metadata()
    {
        $headers = ['X-Object-Meta-Bar' => 'Foo'];

        $this->setupMock('POST', self::CONTAINER . '/' . self::NAME, null, $headers, 'NoContent');

        $this->object->resetMetadata(['Bar' => 'Foo']);
    }

    public function test_It_Deletes()
    {
        $this->setupMock('DELETE', self::CONTAINER . '/' . self::NAME, null, [], 'NoContent');
        $this->object->delete();
    }

    public function test_It_Downloads()
    {
        $this->setupMock('GET', self::CONTAINER . '/' . self::NAME, null, [], 'GET_Object');

        $stream = $this->object->download();

        self::assertInstanceOf(Stream::class, $stream);
        self::assertEquals(14, $stream->getSize());
    }

    public function test_It_Copies()
    {
        $path = self::CONTAINER . '/' . self::NAME;
        $headers = ['Destination' => 'foo/bar'];

        $this->setupMock('COPY', $path, null, $headers, 'Created');

        $this->object->copy([
            'destination' => $headers['Destination']
        ]);
    }

    public function test_It_Gets_Public_Uri()
    {
        $this->client->getConfig('base_uri')
            ->shouldBeCalled()
            ->willReturn(Utils::uriFor('myopenstack.org:9000/tenantId'));

        $this->object->containerName = 'foo';
        $this->object->name = 'bar';

        self::assertEquals(Utils::uriFor('myopenstack.org:9000/tenantId/foo/bar'), $this->object->getPublicUri());
    }
}
