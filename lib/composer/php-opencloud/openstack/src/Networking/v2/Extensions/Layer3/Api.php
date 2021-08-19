<?php

namespace OpenStack\Networking\v2\Extensions\Layer3;

use OpenStack\Common\Api\AbstractApi;

class Api extends AbstractApi
{
    private $pathPrefix = 'v2.0';

    public function __construct()
    {
        $this->params = new Params();
    }

    public function postFloatingIps(): array
    {
        return [
            'method'  => 'POST',
            'path'    => $this->pathPrefix.'/floatingips',
            'jsonKey' => 'floatingip',
            'params'  => [
                'tenantId'          => $this->params->tenantIdJson(),
                'floatingNetworkId' => $this->params->floatingNetworkIdJson(),
                'fixedIpAddress'    => $this->params->fixedIpAddressJson(),
                'floatingIpAddress' => $this->params->floatingIpAddressJson(),
                'portId'            => $this->params->portIdJson(),
            ],
        ];
    }

    public function getFloatingIps(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/floatingips',
            'params' => [
                'tenantId' => $this->params->queryTenantId(),
            ],
        ];
    }

    public function putFloatingIp(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/floatingips/{id}',
            'jsonKey' => 'floatingip',
            'params'  => [
                'id'                => $this->params->idPath(),
                'floatingNetworkId' => $this->notRequired($this->params->floatingNetworkIdJson()),
                'fixedIpAddress'    => $this->params->fixedIpAddressJson(),
                'floatingIpAddress' => $this->params->floatingIpAddressJson(),
                'portId'            => $this->params->portIdJson(),
            ],
        ];
    }

    public function getFloatingIp(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/floatingips/{id}',
            'params' => [
                'id'     => $this->params->idPath(),
                'portId' => $this->params->portIdJson(),
            ],
        ];
    }

    public function deleteFloatingIp(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/floatingips/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }

    public function postRouters(): array
    {
        return [
            'method'  => 'POST',
            'path'    => $this->pathPrefix.'/routers',
            'jsonKey' => 'router',
            'params'  => [
                'name'                => $this->params->nameJson(),
                'externalGatewayInfo' => $this->params->externalGatewayInfo(),
                'adminStateUp'        => $this->params->adminStateUp(),
                'tenantId'            => $this->params->tenantId(),
                'distributed'         => $this->params->distributedJson(),
                'ha'                  => $this->params->haJson(),
            ],
        ];
    }

    public function getRouters(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/routers',
            'params' => [
                'name'     => $this->params->queryName(),
                'tenantId' => $this->params->queryTenantId(),
            ],
        ];
    }

    public function putRouter(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/routers/{id}',
            'jsonKey' => 'router',
            'params'  => [
                'id'                  => $this->params->idPath(),
                'name'                => $this->params->nameJson(),
                'externalGatewayInfo' => $this->params->externalGatewayInfo(),
                'adminStateUp'        => $this->params->adminStateUp(),
            ],
        ];
    }

    public function getRouter(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/routers/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }

    public function deleteRouter(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/routers/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }

    public function putAddInterface()
    {
        return [
            'method' => 'PUT',
            'path'   => $this->pathPrefix.'/routers/{id}/add_router_interface',
            'params' => [
                'id'       => $this->params->idPath(),
                'subnetId' => $this->params->subnetId(),
                'portId'   => $this->params->portIdJson(),
            ],
        ];
    }

    public function putRemoveInterface()
    {
        return [
            'method' => 'PUT',
            'path'   => $this->pathPrefix.'/routers/{id}/remove_router_interface',
            'params' => [
                'id'       => $this->params->idPath(),
                'subnetId' => $this->params->subnetId(),
                'portId'   => $this->params->portIdJson(),
            ],
        ];
    }
}
