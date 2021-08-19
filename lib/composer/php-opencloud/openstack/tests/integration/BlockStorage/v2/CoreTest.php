<?php

namespace OpenStack\integration\BlockStorage\v2;

use OpenStack\BlockStorage\v2\Models\Snapshot;
use OpenStack\BlockStorage\v2\Models\Volume;
use OpenStack\BlockStorage\v2\Models\VolumeType;
use OpenStack\Integration\TestCase;
use OpenStack\Integration\Utils;

class CoreTest extends TestCase
{
    private $service;

    /**
     * @return \OpenStack\BlockStorage\v2\Service
     */
    private function getService()
    {
        if (null === $this->service) {
            $this->service = Utils::getOpenStack()->blockStorageV2();
        }

        return $this->service;
    }

    public function runTests()
    {
        $this->startTimer();

        $this->logger->info('-> Volumes');
        $this->volumes();
        $this->logger->info('-> Volume Types');
        $this->volumeTypes();
        $this->logger->info('-> Snapshots');
        $this->snapshots();

        $this->outputTimeTaken();
    }

    public function volumes()
    {
        $this->logStep('-> Volumes tests');
        $volumeType = $this->getService()->createVolumeType(['name' => $this->randomStr()]);

        $replacements = [
            '{description}' => $this->randomStr(),
            "'{size}'"      => 1,
            '{name}'        => $this->randomStr(),
            '{volumeType}'  => $volumeType->id,
            '{key1}'        => $this->randomStr(),
            '{val1}'        => $this->randomStr(),
        ];

        $this->logStep('Creating volume');
        /** @var Volume $volume */
        require_once $this->sampleFile($replacements, 'volumes/create.php');
        self::assertInstanceOf(Volume::class, $volume);
        self::assertEquals($replacements['{name}'], $volume->name);
        self::assertEquals(1, $volume->size);
        self::assertEquals($volumeType->name, $volume->volumeTypeName);

        $volumeId = $volume->id;
        $replacements = ['{volumeId}' => $volumeId];

        $this->logStep('Getting volume');
        /** @var Volume $volume */
        require_once $this->sampleFile($replacements, 'volumes/get.php');
        self::assertInstanceOf(Volume::class, $volume);

        $replacements += ['{newName}' => $this->randomStr(), '{newDescription}' => $this->randomStr()];

        $this->logStep('Updating volume');
        /** @var Volume $volume */
        require_once $this->sampleFile($replacements, 'volumes/update.php');
        self::assertInstanceOf(Volume::class, $volume);

        $this->logStep('Listing volumes');
        /** @var \Generator $volumes */
        require_once $this->sampleFile($replacements, 'volumes/list.php');

        $this->logStep('Deleting volume');
        require_once $this->sampleFile($replacements, 'volumes/delete.php');

        $volume = $this->getService()->getVolume($volumeId);
        $volume->waitUntilDeleted();

        $this->logStep('Deleting volume type');
        $volumeType->delete();
    }

    public function volumeTypes()
    {
        $replacements = [
            '{name}' => $this->randomStr(),
        ];

        $this->logStep('Creating volume type');
        /** @var VolumeType $volumeType */
        require_once $this->sampleFile($replacements, 'volume_types/create.php');
        self::assertInstanceOf(VolumeType::class, $volumeType);
        self::assertEquals($replacements['{name}'], $volumeType->name);

        $replacements = ['{volumeTypeId}' => $volumeType->id];

        $this->logStep('Getting volume type');
        /** @var VolumeType $volumeType */
        require_once $this->sampleFile($replacements, 'volume_types/get.php');
        self::assertInstanceOf(VolumeType::class, $volumeType);

        $replacements += ['{newName}' => $this->randomStr()];

        $this->logStep('Updating volume type');
        /** @var VolumeType $volumeType */
        require_once $this->sampleFile($replacements, 'volume_types/update.php');
        self::assertInstanceOf(VolumeType::class, $volumeType);

        $this->logStep('Listing volume types');
        /** @var \Generator $volumeTypes */
        require_once $this->sampleFile($replacements, 'volume_types/list.php');

        $this->logStep('Deleting volume type');
        require_once $this->sampleFile($replacements, 'volume_types/delete.php');
    }

    public function snapshots()
    {
        $this->logStep('Creating volume');
        $volume = $this->getService()->createVolume(['name' => $this->randomStr(), 'size' => 1]);
        $volume->waitUntil('available', 60);

        $replacements = [
            '{volumeId}'    => $volume->id,
            '{name}'        => $this->randomStr(),
            '{description}' => $this->randomStr(),
        ];

        $this->logStep('Creating snapshot');
        /** @var Snapshot $snapshot */
        require_once $this->sampleFile($replacements, 'snapshots/create.php');
        self::assertInstanceOf(Snapshot::class, $snapshot);
        self::assertEquals($replacements['{name}'], $snapshot->name);
        $volume->waitUntil('available', 60);

        $snapshotId = $snapshot->id;
        $replacements = ['{snapshotId}' => $snapshotId];

        $this->logStep('Getting snapshot');
        /** @var Snapshot $snapshot */
        require_once $this->sampleFile($replacements, 'snapshots/get.php');
        self::assertInstanceOf(Snapshot::class, $snapshot);

        $this->getService()
            ->getSnapshot($snapshot->id)
            ->mergeMetadata(['key1' => 'val1']);

        $replacements += ['{key}' => 'key2', '{val}' => 'val2'];
        $this->logStep('Adding metadata');
        require_once $this->sampleFile($replacements, 'snapshots/merge_metadata.php');

        $this->logStep('Retrieving metadata');
        /** @var array $metadata */
        require_once $this->sampleFile($replacements, 'snapshots/get_metadata.php');
        self::assertEquals(['key1' => 'val1', 'key2' => 'val2'], $metadata);

        $replacements = ['{snapshotId}' => $snapshot->id, '{key}' => 'key3', '{val}' => 'val3'];
        $this->logStep('Resetting metadata');
        require_once $this->sampleFile($replacements, 'snapshots/reset_metadata.php');

        $this->logStep('Retrieving metadata');
        /** @var array $metadata */
        require_once $this->sampleFile($replacements, 'snapshots/get_metadata.php');
        self::assertEquals(['key3' => 'val3'], $metadata);

        $replacements += ['{newName}' => $this->randomStr(), '{newDescription}' => $this->randomStr()];
        $this->logStep('Updating snapshot');
        require_once $this->sampleFile($replacements, 'snapshots/update.php');

        $this->logStep('Deleting snapshot');
        require_once $this->sampleFile($replacements, 'snapshots/delete.php');
        $snapshot->waitUntilDeleted();

        $this->logStep('Deleting volume');
        $volume->delete();
    }
}
