<?php

declare(strict_types=1);

namespace OpenStack\BlockStorage\v2;

use OpenStack\BlockStorage\v2\Models\QuotaSet;
use OpenStack\BlockStorage\v2\Models\Snapshot;
use OpenStack\BlockStorage\v2\Models\Volume;
use OpenStack\BlockStorage\v2\Models\VolumeType;
use OpenStack\Common\Service\AbstractService;

/**
 * @property \OpenStack\BlockStorage\v2\Api $api
 */
class Service extends AbstractService
{
    /**
     * Provisions a new bootable volume, based either on an existing volume, image or snapshot.
     * You must have enough volume storage quota remaining to create a volume of size requested.
     *
     * @param array $userOptions {@see Api::postVolumes}
     */
    public function createVolume(array $userOptions): Volume
    {
        return $this->model(Volume::class)->create($userOptions);
    }

    /**
     * Lists all available volumes.
     *
     * @param bool  $detail      if set to TRUE, more information will be returned
     * @param array $userOptions {@see Api::getVolumes}
     */
    public function listVolumes(bool $detail = false, array $userOptions = []): \Generator
    {
        $def = (true === $detail) ? $this->api->getVolumesDetail() : $this->api->getVolumes();

        return $this->model(Volume::class)->enumerate($def, $userOptions);
    }

    /**
     * @param string $volumeId the UUID of the volume being retrieved
     */
    public function getVolume(string $volumeId): Volume
    {
        $volume = $this->model(Volume::class);
        $volume->populateFromArray(['id' => $volumeId]);

        return $volume;
    }

    /**
     * @param array $userOptions {@see Api::postTypes}
     */
    public function createVolumeType(array $userOptions): VolumeType
    {
        return $this->model(VolumeType::class)->create($userOptions);
    }

    public function listVolumeTypes(): \Generator
    {
        return $this->model(VolumeType::class)->enumerate($this->api->getTypes(), []);
    }

    public function getVolumeType(string $typeId): VolumeType
    {
        $type = $this->model(VolumeType::class);
        $type->populateFromArray(['id' => $typeId]);

        return $type;
    }

    /**
     * @param array $userOptions {@see Api::postSnapshots}
     */
    public function createSnapshot(array $userOptions): Snapshot
    {
        return $this->model(Snapshot::class)->create($userOptions);
    }

    public function listSnapshots(bool $detail = false, array $userOptions = []): \Generator
    {
        $def = (true === $detail) ? $this->api->getSnapshotsDetail() : $this->api->getSnapshots();

        return $this->model(Snapshot::class)->enumerate($def, $userOptions);
    }

    public function getSnapshot(string $snapshotId): Snapshot
    {
        $snapshot = $this->model(Snapshot::class);
        $snapshot->populateFromArray(['id' => $snapshotId]);

        return $snapshot;
    }

    /**
     * Shows A Quota for a tenant.
     */
    public function getQuotaSet(string $tenantId): QuotaSet
    {
        $quotaSet = $this->model(QuotaSet::class);
        $quotaSet->populateFromResponse($this->execute($this->api->getQuotaSet(), ['tenantId' => $tenantId]));

        return $quotaSet;
    }
}
