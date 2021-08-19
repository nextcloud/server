<?php

namespace OpenStack\Test\BlockStorage\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\BlockStorage\v2\Api;
use OpenStack\BlockStorage\v2\Models\Volume;
use OpenStack\Test\TestCase;

class VolumeTest extends TestCase
{
    /** @var Volume */
    private $volume;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->volume = new Volume($this->client->reveal(), new Api());
        $this->volume->id = '1';
    }

    public function test_it_updates()
    {
        $this->volume->name = 'foo';
        $this->volume->description = 'bar';

        $expectedJson = ['volume' => ['name' => 'foo', 'description' => 'bar']];
        $this->setupMock('PUT', 'volumes/1', $expectedJson, [], 'GET_volume');

        $this->volume->update();
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'volumes/1', null, [], new Response(204));

        $this->volume->delete();
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'volumes/1', null, [], 'GET_volume');

        $this->volume->retrieve();

        $volumeImageMetadata = $this->volume->volumeImageMetadata;

        self::assertIsArray($volumeImageMetadata);
        self::assertEquals($volumeImageMetadata['os_distro'], 'ubuntu');
        self::assertEquals($volumeImageMetadata['os_version'], 'xenial');
        self::assertEquals($volumeImageMetadata['hypervisor_type'], 'qemu');
        self::assertEquals($volumeImageMetadata['os_variant'], 'ubuntu');
        self::assertEquals($volumeImageMetadata['disk_format'], 'qcow2');
        self::assertEquals($volumeImageMetadata['image_name'], 'Some Image Name x86_64');
        self::assertEquals($volumeImageMetadata['image_id'], '54986297-8364-4baa-8435-812add437507');
        self::assertEquals($volumeImageMetadata['architecture'], 'x86_64');
        self::assertEquals($volumeImageMetadata['container_format'], 'bare');
        self::assertEquals($volumeImageMetadata['min_disk'], '40');
        self::assertEquals($volumeImageMetadata['os_type'], 'linux');
        self::assertEquals($volumeImageMetadata['checksum'], 'bb3055b274fe72bc3406ffe9febe9fff');
        self::assertEquals($volumeImageMetadata['min_ram'], '0');
        self::assertEquals($volumeImageMetadata['size'], '6508557824');
    }

    public function test_it_merges_metadata()
    {
        $this->setupMock('GET', 'volumes/1/metadata', null, [], 'GET_metadata');

        $expectedJson = ['metadata' => [
            'foo' => 'newFoo',
            'bar' => '2',
            'baz' => 'bazVal',
        ]];

        $this->setupMock('PUT', 'volumes/1/metadata', $expectedJson, [], 'GET_metadata');

        $this->volume->mergeMetadata(['foo' => 'newFoo', 'baz' => 'bazVal']);
    }

    public function test_it_resets_metadata()
    {
        $expectedJson = ['metadata' => ['key1' => 'val1']];

        $this->setupMock('PUT', 'volumes/1/metadata', $expectedJson, [], 'GET_metadata');

        $this->volume->resetMetadata(['key1' => 'val1']);
    }

    public function test_it_sets_volume_bootable()
    {
        $this->setupMock('POST', 'volumes/1/action', ['os-set_bootable' => ['bootable' => 'True']], [], new Response(200));

        $this->volume->setBootable(true);
    }

    public function test_it_sets_image_meta_data()
    {
        $expectedJson = [
            'os-set_image_metadata' => [
                'metadata' => [
                    'attr_foo' => 'foofoo',
                    'attr_bar' => 'barbar',
                ],
            ],
        ];

        $this->setupMock('POST', 'volumes/1/action', $expectedJson, [], new Response(200));
        $this->volume->setImageMetadata([
            'attr_foo' => 'foofoo',
            'attr_bar' => 'barbar',
        ]);
    }

    public function test_it_resets_status()
    {
        $expectedJson = ['os-reset_status' => ['status' => 'available', 'attach_status' => 'detached', 'migration_status' => 'migrating']];

        $this->setupMock('POST', 'volumes/1/action', $expectedJson, [], new Response(202));

        $this->volume->resetStatus(
            [
                'status'          => 'available',
                'attachStatus'    => 'detached',
                'migrationStatus' => 'migrating',
            ]
        );
    }
}
