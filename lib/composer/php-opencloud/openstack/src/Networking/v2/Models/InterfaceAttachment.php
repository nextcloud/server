<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2\Models;

use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;

/**
 * @property \OpenStack\Networking\v2\Api $api
 */
class InterfaceAttachment extends OperatorResource implements Listable
{
    /** @var string */
    public $portId;

    /** @var string */
    public $netId;

    /** @var string */
    public $macAddr;

    /** @var string */
    public $subnetId;

    /** @var string */
    public $ipAddress;

    /** @var array */
    public $fixedIps;

    /** @var string */
    public $portState;

    /** @var string */
    public $serverId;

    protected $resourceKey  = 'interfaceAttachment';
    protected $resourcesKey = 'interfaceAttachments';

    protected $aliases = [
      'port_id'    => 'portId',
      'net_id'     => 'netId',
      'mac_addr'   => 'macAddr',
      'subnet_id'  => 'subnetId',
      'ip_address' => 'ipAddress',
      'fixed_ips'  => 'fixedIps',
      'port_state' => 'portState',
      'server_id'  => 'serverId',
    ];
}
