<?php

declare(strict_types=1);

namespace OpenStack\BlockStorage\v2\Models;

use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;

/**
 * Represents a BlockStorage v2 Quota Set.
 *
 * @property \OpenStack\BlockStorage\v2\Api $api
 */
class QuotaSet extends OperatorResource implements Retrievable, Updateable, Deletable
{
    /** @var string */
    public $tenantId;

    /** @var int */
    public $backupGigabytes;

    /** @var int */
    public $backups;

    /** @var int */
    public $gigabytes;

    /** @var int */
    public $gigabytesIscsi;

    /** @var int */
    public $perVolumeGigabytes;

    /** @var int */
    public $snapshots;

    /** @var int */
    public $snapshotsIscsi;

    /** @var int */
    public $volumes;

    /** @var int */
    public $volumesIscsi;

    protected $aliases = [
        'backup_gigabytes'     => 'backupGigabytes',
        'gigabytes'            => 'gigabytes',
        'gigabytes_iscsi'      => 'gigabytesIscsi',
        'per_volume_gigabytes' => 'perVolumeGigabytes',
        'snapshots_iscsi'      => 'snapshotsIscsi',
        'volumes_iscsi'        => 'volumesIscsi',
        'id'                   => 'tenantId',
    ];

    protected $resourceKey = 'quota_set';

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getQuotaSet(), ['tenantId' => (string) $this->tenantId]);
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->putQuotaSet());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $response = $this->executeWithState($this->api->deleteQuotaSet());
        $this->populateFromResponse($response);
    }
}
