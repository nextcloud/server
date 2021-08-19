<?php

namespace OpenStack\Networking\v2\Extensions\Layer3\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\HasWaiterTrait;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;
use OpenStack\Networking\v2\Extensions\Layer3\Api;

/**
 * @property Api $api
 */
class Router extends OperatorResource implements Listable, Creatable, Retrievable, Updateable, Deletable
{
    use HasWaiterTrait;

    /** @var string */
    public $status;

    /** @var GatewayInfo */
    public $externalGatewayInfo;

    /** @var string */
    public $name;

    /** @var string */
    public $adminStateUp;

    /** @var string */
    public $tenantId;

    /** @var array */
    public $routes;

    /** @var string */
    public $id;

    protected $resourceKey = 'router';

    protected $aliases = [
        'admin_state_up' => 'adminStateUp',
        'tenant_id'      => 'tenantId',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'external_gateway_info' => new Alias('externalGatewayInfo', GatewayInfo::class),
        ];
    }

    public function create(array $userOptions): Creatable
    {
        $response = $this->execute($this->api->postRouters(), $userOptions);

        return $this->populateFromResponse($response);
    }

    public function update()
    {
        $response = $this->executeWithState($this->api->putRouter());
        $this->populateFromResponse($response);
    }

    public function retrieve()
    {
        $response = $this->executeWithState($this->api->getRouter());
        $this->populateFromResponse($response);
    }

    public function delete()
    {
        $this->executeWithState($this->api->deleteRouter());
    }

    /**
     * @param array $userOptions {@see \OpenStack\Networking\v2\Extensions\Layer3\Api::putAddInterface}
     */
    public function addInterface(array $userOptions)
    {
        $userOptions['id'] = $this->id;
        $this->execute($this->api->putAddInterface(), $userOptions);
    }

    /**
     * @param array $userOptions {@see \OpenStack\Networking\v2\Extensions\Layer3\Api::putRemoveInterface}
     */
    public function removeInterface(array $userOptions)
    {
        $userOptions['id'] = $this->id;
        $this->execute($this->api->putRemoveInterface(), $userOptions);
    }
}
