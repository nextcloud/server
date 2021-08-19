<?php

namespace unit\BlockStorage\v2;

use GuzzleHttp\Psr7\Response;
use OpenStack\BlockStorage\v2\Api;
use OpenStack\BlockStorage\v2\Models\QuotaSet;
use OpenStack\BlockStorage\v2\Models\Snapshot;
use OpenStack\BlockStorage\v2\Models\Volume;
use OpenStack\BlockStorage\v2\Models\VolumeType;
use OpenStack\BlockStorage\v2\Service;
use OpenStack\Test\TestCase;

class ServiceTest extends TestCase
{
    /** @var Service */
    private $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = __DIR__;

        $this->service = new Service($this->client->reveal(), new Api());
    }

    public function test_it_creates_volumes()
    {
        $opts = [
            "description"      => '1',
            "availabilityZone" => '2',
            "sourceVolumeId"   => '3',
            "snapshotId"       => '4',
            "size"             => 6,
            "name"             => '7',
            "imageId"          => '8',
            "volumeType"       => '9',
            "metadata"         => [
                'foo' => '1',
                'bar' => '2',
            ],
        ];

        $expectedJson = [
            'volume' => [
                "description"       => '1',
                "availability_zone" => '2',
                "source_volid"      => '3',
                "snapshot_id"       => '4',
                "size"              => 6,
                "name"              => '7',
                "imageRef"          => '8',
                "volume_type"       => '9',
                "metadata"          => [
                    'foo' => '1',
                    'bar' => '2',
                ],
            ],
        ];

        $this->setupMock('POST', 'volumes', $expectedJson, [], 'GET_volume');

        self::assertInstanceOf(Volume::class, $this->service->createVolume($opts));
    }

    public function test_it_lists_volumes()
    {
        $this->client
            ->request('GET', 'volumes', ['headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('GET_volumes'));

        $this->client
            ->request('GET', 'volumes', ['query' => ['marker' => '5aa119a8-d25b-45a7-8d1b-88e127885635'], 'headers' => []])
            ->shouldBeCalled()
            ->willReturn(new Response(204));

        $count = 0;

        foreach ($this->service->listVolumes(false) as $volume) {
            $count++;
            self::assertInstanceOf(Volume::class, $volume);
        }

        self::assertEquals(2, $count);
    }

    public function test_it_gets_a_volume()
    {
        $volume = $this->service->getVolume('volumeId');

        self::assertInstanceOf(Volume::class, $volume);
        self::assertEquals('volumeId', $volume->id);
    }

    public function test_it_creates_volume_types()
    {
        $opts = ['name' => 'foo'];

        $expectedJson = ['volume_type' => $opts];

        $this->setupMock('POST', 'types', $expectedJson, [], 'GET_type');

        self::assertInstanceOf(VolumeType::class, $this->service->createVolumeType($opts));
    }

    public function test_it_lists_volume_types()
    {
        $this->client
            ->request('GET', 'types', ['headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('GET_types'));

        $count = 0;

        foreach ($this->service->listVolumeTypes() as $type) {
            $count++;
            self::assertInstanceOf(VolumeType::class, $type);
        }

        self::assertEquals(2, $count);
    }

    public function test_it_gets_a_volume_type()
    {
        $type = $this->service->getVolumeType('id');

        self::assertInstanceOf(VolumeType::class, $type);
        self::assertEquals('id', $type->id);
    }

    public function test_it_creates_snapshots()
    {
        $opts = [
            'name'        => 'snap-001',
            'description' => 'Daily backup',
            'volumeId'    => '5aa119a8-d25b-45a7-8d1b-88e127885635',
            'force'       => true,
        ];

        $expectedJson = ['snapshot' => [
            'name'        => $opts['name'],
            'description' => $opts['description'],
            'volume_id'   => $opts['volumeId'],
            'force'       => $opts['force'],
        ]];

        $this->setupMock('POST', 'snapshots', $expectedJson, [], 'GET_snapshot');

        self::assertInstanceOf(Snapshot::class, $this->service->createSnapshot($opts));
    }

    public function test_it_lists_snapshots()
    {
        $this->client
            ->request('GET', 'snapshots', ['headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('GET_snapshots'));

        $this->client
            ->request('GET', 'snapshots', ['query' => ['marker' => 'e820db06-58b5-439d-bac6-c01faa3f6499'], 'headers' => []])
            ->shouldBeCalled()
            ->willReturn(new Response(204));

        $count = 0;

        foreach ($this->service->listSnapshots(false) as $snapshot) {
            $count++;
            self::assertInstanceOf(Snapshot::class, $snapshot);
        }

        self::assertEquals(2, $count);
    }

    public function test_it_gets_a_snapshot()
    {
        $snapshot = $this->service->getSnapshot('snapshotId');

        self::assertInstanceOf(Snapshot::class, $snapshot);
        self::assertEquals('snapshotId', $snapshot->id);
    }

    public function test_it_gets_quota_set()
    {
        $this->client
            ->request('GET', 'os-quota-sets/tenant-id-1234', ['headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('GET_quota_set'));

        $quotaSet = $this->service->getQuotaSet('tenant-id-1234');

        self::assertInstanceOf(QuotaSet::class, $quotaSet);
        self::assertEquals(1, $quotaSet->gigabytes);
        self::assertEquals(2, $quotaSet->snapshots);
        self::assertEquals(3, $quotaSet->volumes);
    }
}
