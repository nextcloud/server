<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2\Models;

use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;

/**
 * @property \OpenStack\Compute\v2\Api $api
 */
class Hypervisor extends OperatorResource implements Retrievable, Listable
{
    /** @var int */
    public $id;

    /** @var string */
    public $status;

    /** @var string */
    public $state;

    /** @var string */
    public $hostIp;

    /** @var int */
    public $freeDiskGb;

    /** @var int */
    public $freeRamMb;

    /** @var string */
    public $hypervisorHostname;

    /** @var string */
    public $hypervisorType;

    /** @var string */
    public $hypervisorVersion;

    /** @var int */
    public $localGb;

    /** @var int */
    public $localGbUsed;

    /** @var int */
    public $memoryMb;

    /** @var int */
    public $memoryMbUsed;

    /** @var int */
    public $runningVms;

    /** @var int */
    public $vcpus;

    /** @var int */
    public $vcpusUsed;

    /** @var array */
    public $cpuInfo;

    /** @var int */
    public $currentWorkload;

    /** @var int */
    public $diskAvailableLeast;

    /** @var array */
    public $service;

    protected $resourceKey  = 'hypervisor';
    protected $resourcesKey = 'hypervisors';

    protected $aliases = [
      'host_ip'              => 'hostIp',
      'free_disk_gb'         => 'freeDiskGb',
      'free_ram_mb'          => 'freeRamMb',
      'hypervisor_hostname'  => 'hypervisorHostname',
      'hypervisor_type'      => 'hypervisorType',
      'hypervisor_version'   => 'hypervisorVersion',
      'local_gb'             => 'localGb',
      'local_gb_used'        => 'localGbUsed',
      'memory_mb'            => 'memoryMb',
      'memory_mb_used'       => 'memoryMbUsed',
      'running_vms'          => 'runningVms',
      'vcpus_used'           => 'vcpusUsed',
      'cpu_info'             => 'cpuInfo',
      'current_workload'     => 'currentWorkload',
      'disk_available_least' => 'diskAvailableLeast',
    ];

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getHypervisor(), ['id' => (string) $this->id]);
        $this->populateFromResponse($response);
    }
}
