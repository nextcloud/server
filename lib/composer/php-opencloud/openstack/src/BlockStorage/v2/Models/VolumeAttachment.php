<?php

declare(strict_types=1);

namespace OpenStack\BlockStorage\v2\Models;

use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;

/**
 * @property \OpenStack\BlockStorage\v2\Api $api
 */
class VolumeAttachment extends OperatorResource implements Listable
{
    /** @var string */
    public $id;

    /** @var int */
    public $device;

    /** @var string */
    public $serverId;

    /** @var string */
    public $volumeId;

    protected $resourceKey  = 'volumeAttachment';
    protected $resourcesKey = 'volumeAttachments';
}
