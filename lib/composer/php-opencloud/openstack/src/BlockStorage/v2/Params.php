<?php

declare(strict_types=1);

namespace OpenStack\BlockStorage\v2;

use OpenStack\Common\Api\AbstractParams;

class Params extends AbstractParams
{
    public function availabilityZone(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'availability_zone',
            'description' => 'The availability zone where the entity will reside.',
        ];
    }

    public function sourceVolId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'source_volid',
            'description' => 'To create a volume from an existing volume, specify the ID of the existing volume. The '.
                'volume is created with the same size as the source volume.',
        ];
    }

    public function desc(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'description' => 'A human-friendly description that describes the resource',
        ];
    }

    public function snapshotId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'snapshot_id',
            'description' => 'To create a volume from an existing snapshot, specify the ID of the existing volume '.
                'snapshot. The volume is created in same availability zone and with same size as the snapshot.',
        ];
    }

    public function size(): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::JSON,
            'required'    => true,
            'description' => 'The size of the volume, in gibibytes (GiB).',
        ];
    }

    public function imageRef(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'imageRef',
            'description' => 'The ID of the image from which you want to create the volume. Required to create a '.
                'bootable volume.',
        ];
    }

    public function volumeType(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'volume_type',
            'description' => 'The associated volume type.',
        ];
    }

    public function bootable(): array
    {
        return [
            'type'        => self::BOOL_TYPE,
            'location'    => self::JSON,
            'description' => 'Enables or disables the bootable attribute. You can boot an instance from a bootable volume.',
        ];
    }

    public function volumeStatus(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'required'    => true,
            'description' => 'The volume status.',
        ];
    }

    public function volumeMigrationStatus(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'required'    => false,
            'description' => 'The volume migration status.',
            'sentAs'      => 'migration_status',
        ];
    }

    public function volumeAttachStatus(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'required'    => false,
            'description' => 'The volume attach status.',
            'sentAs'      => 'attach_status',
        ];
    }

    public function metadata(): array
    {
        return [
            'type'        => self::OBJECT_TYPE,
            'location'    => self::JSON,
            'description' => 'One or more metadata key and value pairs to associate with the volume.',
            'properties'  => [
                'type'        => self::STRING_TYPE,
                'description' => <<<TYPEOTHER
The value being set for your key. Bear in mind that "key" is just an example, you can name it anything.
TYPEOTHER
            ],
        ];
    }

    public function sort(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'description' => 'Comma-separated list of sort keys and optional sort directions in the form of '.
                '<key>[:<direction>]. A valid direction is asc (ascending) or desc (descending).',
        ];
    }

    public function name(string $resource): array
    {
        return parent::name($resource) + [
            'type'     => self::STRING_TYPE,
            'location' => self::JSON,
        ];
    }

    public function idPath(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::URL,
            'description' => 'The UUID of the resource',
            'documented'  => false,
        ];
    }

    public function typeSpecs(): array
    {
        return [
            'type'        => self::OBJECT_TYPE,
            'location'    => self::JSON,
            'description' => 'A key and value pair that contains additional specifications that are associated with '.
                'the volume type. Examples include capabilities, capacity, compression, and so on, depending on the '.
                'storage driver in use.',
        ];
    }

    public function volId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'required'    => true,
            'sentAs'      => 'volume_id',
            'description' => 'To create a snapshot from an existing volume, specify the ID of the existing volume.',
        ];
    }

    public function force(): array
    {
        return [
            'type'        => self::BOOL_TYPE,
            'location'    => self::JSON,
            'description' => 'Indicate whether to snapshot, even if the volume is attached. Default is false.',
        ];
    }

    public function snapshotName(): array
    {
        return parent::name('snapshot') + [
            'type'     => self::STRING_TYPE,
            'location' => self::JSON,
        ];
    }

    protected function quotaSetLimit($sentAs, $description): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::JSON,
            'sentAs'      => $sentAs,
            'description' => $description,
        ];
    }

    public function quotaSetLimitInstances(): array
    {
        return $this->quotaSetLimit('instances', 'The number of allowed instances for each tenant.');
    }

    public function quotaSetBackupGigabytes(): array
    {
        return $this->quotaSetLimit('backup_gigabytes', 'Total size of back-up storage (GiB)');
    }

    public function quotaSetBackups(): array
    {
        return $this->quotaSetLimit('backups', 'The number of allowed back-ups');
    }

    public function quotaSetGigabytes(): array
    {
        return $this->quotaSetLimit('gigabytes', 'Total Size of Volumes and Snapshots (GiB)');
    }

    public function quotaSetGigabytesIscsi(): array
    {
        return $this->quotaSetLimit('gigabytes_iscsi', 'Total Size of Volumes and Snapshots iscsi (GiB)');
    }

    public function quotaSetTenantId(): array
    {
        return $this->quotaSetLimit('id', 'Tenant Id');
    }

    public function quotaSetPerVolumeGigabytes(): array
    {
        return $this->quotaSetLimit('per_volume_gigabytes', 'Allowed size per Volume (GiB)');
    }

    public function quotaSetSnapshots(): array
    {
        return $this->quotaSetLimit('snapshots', 'The number of allowed snapshots');
    }

    public function quotaSetSnapshotsIscsi(): array
    {
        return $this->quotaSetLimit('snapshots_iscsi', 'The number of allowed snapshots iscsi');
    }

    public function quotaSetVolumes(): array
    {
        return $this->quotaSetLimit('volumes', 'The number of allowed volumes');
    }

    public function quotaSetVolumesIscsi(): array
    {
        return $this->quotaSetLimit('volumes_iscsi', 'The number of allowed volumes iscsi');
    }

    public function projectId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::URL,
            'sentAs'      => 'project_id',
            'description' => 'The UUID of the project in a multi-tenancy cloud.',
        ];
    }
}
