<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2\Models;

use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;
use OpenStack\Networking\v2\Api;

/**
 * Represents a Neutron v2 Quota.
 *
 * @property Api $api
 */
class Quota extends OperatorResource implements Retrievable, Updateable, Deletable
{
    /**
     * @var int
     */
    public $subnet;

    /**
     * @var int
     */
    public $network;

    /**
     * @var int
     */
    public $floatingip;

    /**
     * @var string
     */
    public $tenantId;

    /**
     * @var int
     */
    public $subnetpool;

    /**
     * @var int
     */
    public $securityGroupRule;

    /**
     * @var int
     */
    public $securityGroup;

    /**
     * @var int
     */
    public $router;

    /**
     * @var int
     */
    public $rbacPolicy;

    /**
     * @var int
     */
    public $port;

    protected $resourcesKey = 'quotas';
    protected $resourceKey  = 'quota';

    protected $aliases = [
        'tenant_id'           => 'tenantId',
        'security_group_rule' => 'securityGroupRule',
        'security_group'      => 'securityGroup',
        'rbac_policy'         => 'rbacPolicy',
    ];

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getQuota(), ['tenantId' => (string) $this->tenantId]);
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->putQuota());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteQuota());
    }
}
