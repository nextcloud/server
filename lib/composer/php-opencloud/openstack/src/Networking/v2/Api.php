<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2;

use OpenStack\Common\Api\AbstractApi;

/**
 * A representation of the Neutron (Nova) v2 REST API.
 *
 * @internal
 */
class Api extends AbstractApi
{
    private $pathPrefix = 'v2.0';

    public function __construct()
    {
        $this->params = new Params();
    }

    public function getNetwork(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/networks/{id}',
            'params' => ['id' => $this->params->urlId('network')],
        ];
    }

    public function getNetworks(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/networks',
            'params' => [
                'name'           => $this->params->queryName(),
                'tenantId'       => $this->params->queryTenantId(),
                'status'         => $this->params->queryStatus(),
                'routerExternal' => $this->params->queryRouterExternal(),
            ],
        ];
    }

    public function postNetwork(): array
    {
        return [
            'path'    => $this->pathPrefix.'/networks',
            'method'  => 'POST',
            'jsonKey' => 'network',
            'params'  => [
                'name'             => $this->params->name('network'),
                'shared'           => $this->params->shared(),
                'adminStateUp'     => $this->params->adminStateUp(),
                'routerAccessible' => $this->params->routerAccessibleJson(),
                'tenantId'         => $this->params->tenantId(),
            ],
        ];
    }

    public function postNetworks(): array
    {
        return [
            'path'    => $this->pathPrefix.'/networks',
            'method'  => 'POST',
            'jsonKey' => '',
            'params'  => [
                'networks' => [
                    'type'        => 'array',
                    'description' => 'List of networks',
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'name'         => $this->params->name('network'),
                            'shared'       => $this->params->shared(),
                            'adminStateUp' => $this->params->adminStateUp(),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function putNetwork(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/networks/{id}',
            'jsonKey' => 'network',
            'params'  => [
                'id'           => $this->params->urlId('network'),
                'name'         => $this->params->name('network'),
                'shared'       => $this->params->shared(),
                'adminStateUp' => $this->params->adminStateUp(),
            ],
        ];
    }

    public function deleteNetwork(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/networks/{id}',
            'params' => ['id' => $this->params->urlId('network')],
        ];
    }

    public function getSubnet(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/subnets/{id}',
            'params' => ['id' => $this->params->urlId('network')],
        ];
    }

    public function getSubnets(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/subnets',
            'params' => [
                'name'     => $this->params->queryName(),
                'tenantId' => $this->params->queryTenantId(),
            ],
        ];
    }

    public function postSubnet(): array
    {
        return [
            'path'    => $this->pathPrefix.'/subnets',
            'method'  => 'POST',
            'jsonKey' => 'subnet',
            'params'  => [
                'name'            => $this->params->name('subnet'),
                'networkId'       => $this->isRequired($this->params->networkId()),
                'ipVersion'       => $this->isRequired($this->params->ipVersion()),
                'cidr'            => $this->isRequired($this->params->cidr()),
                'tenantId'        => $this->params->tenantId(),
                'gatewayIp'       => $this->params->gatewayIp(),
                'enableDhcp'      => $this->params->enableDhcp(),
                'dnsNameservers'  => $this->params->dnsNameservers(),
                'allocationPools' => $this->params->allocationPools(),
                'hostRoutes'      => $this->params->hostRoutes(),
            ],
        ];
    }

    public function postSubnets(): array
    {
        return [
            'path'   => $this->pathPrefix.'/subnets',
            'method' => 'POST',
            'params' => [
                'subnets' => [
                    'type'        => Params::ARRAY_TYPE,
                    'description' => 'List of subnets',
                    'items'       => [
                        'type'       => Params::OBJECT_TYPE,
                        'properties' => $this->postSubnet()['params'],
                    ],
                ],
            ],
        ];
    }

    public function putSubnet(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/subnets/{id}',
            'jsonKey' => 'subnet',
            'params'  => [
                'id'              => $this->params->urlId('subnet'),
                'name'            => $this->params->name('subnet'),
                'gatewayIp'       => $this->params->gatewayIp(),
                'dnsNameservers'  => $this->params->dnsNameservers(),
                'allocationPools' => $this->params->allocationPools(),
                'hostRoutes'      => $this->params->hostRoutes(),
            ],
        ];
    }

    public function deleteSubnet(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/subnets/{id}',
            'params' => ['id' => $this->params->urlId('subnet')],
        ];
    }

    public function getPorts(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/ports',
            'params' => [
                'status'         => $this->params->statusQuery(),
                'displayName'    => $this->params->displayNameQuery(),
                'adminState'     => $this->params->adminStateQuery(),
                'networkId'      => $this->notRequired($this->params->networkId()),
                'tenantId'       => $this->params->tenantIdQuery(),
                'deviceOwner'    => $this->params->deviceOwnerQuery(),
                'macAddress'     => $this->params->macAddrQuery(),
                'portId'         => $this->params->portIdQuery(),
                'securityGroups' => $this->params->secGroupsQuery(),
                'deviceId'       => $this->params->deviceIdQuery(),
            ],
        ];
    }

    public function postSinglePort(): array
    {
        return [
            'method'  => 'POST',
            'path'    => $this->pathPrefix.'/ports',
            'jsonKey' => 'port',
            'params'  => [
                'name'                => $this->params->name('port'),
                'adminStateUp'        => $this->params->adminStateUp(),
                'tenantId'            => $this->params->tenantId(),
                'macAddress'          => $this->params->macAddr(),
                'fixedIps'            => $this->params->fixedIps(),
                'subnetId'            => $this->params->subnetId(),
                'ipAddress'           => $this->params->ipAddress(),
                'securityGroups'      => $this->params->secGroupIds(),
                'networkId'           => $this->params->networkId(),
                'allowedAddressPairs' => $this->params->allowedAddrPairs(),
                'deviceOwner'         => $this->params->deviceOwner(),
                'deviceId'            => $this->params->deviceId(),
                'portSecurityEnabled' => $this->params->portSecurityEnabled(),
            ],
        ];
    }

    public function postMultiplePorts(): array
    {
        return [
            'method' => 'POST',
            'path'   => $this->pathPrefix.'/ports',
            'params' => [
                'ports' => [
                    'type'  => Params::ARRAY_TYPE,
                    'items' => [
                        'type'       => Params::OBJECT_TYPE,
                        'properties' => $this->postSinglePort()['params'],
                    ],
                ],
            ],
        ];
    }

    public function getPort(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/ports/{id}',
            'params' => ['id' => $this->params->idPath()],
        ];
    }

    public function putPort(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/ports/{id}',
            'jsonKey' => 'port',
            'params'  => [
                'id'                  => $this->params->idPath(),
                'name'                => $this->params->name('port'),
                'adminStateUp'        => $this->params->adminStateUp(),
                'tenantId'            => $this->params->tenantId(),
                'macAddress'          => $this->params->macAddr(),
                'fixedIps'            => $this->params->fixedIps(),
                'subnetId'            => $this->params->subnetId(),
                'ipAddress'           => $this->params->ipAddress(),
                'securityGroups'      => $this->params->secGroupIds(),
                'networkId'           => $this->notRequired($this->params->networkId()),
                'allowedAddressPairs' => $this->params->allowedAddrPairs(),
                'deviceOwner'         => $this->params->deviceOwner(),
                'deviceId'            => $this->params->deviceId(),
            ],
        ];
    }

    public function deletePort(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/ports/{id}',
            'params' => ['id' => $this->params->idPath()],
        ];
    }

    public function getQuotas(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/quotas',
            'params' => [],
        ];
    }

    public function getQuota(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/quotas/{tenantId}',
            'params' => [],
        ];
    }

    public function getQuotaDefault(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/quotas/{tenantId}/default',
            'params' => [],
        ];
    }

    public function putQuota(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/quotas/{tenantId}',
            'jsonKey' => 'quota',
            'params'  => [
                'tenantId'          => $this->params->idPath(),
                'floatingip'        => $this->params->quotaLimitFloatingIp(),
                'network'           => $this->params->quotaLimitNetwork(),
                'port'              => $this->params->quotaLimitPort(),
                'rbacPolicy'        => $this->params->quotaLimitRbacPolicy(),
                'router'            => $this->params->quotaLimitRouter(),
                'securityGroup'     => $this->params->quotaLimitSecurityGroup(),
                'securityGroupRule' => $this->params->quotaLimitSecurityGroupRule(),
                'subnet'            => $this->params->quotaLimitSubnet(),
                'subnetpool'        => $this->params->quotaLimitSubnetPool(),
            ],
        ];
    }

    public function deleteQuota(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/quotas/{tenantId}',
            'params' => [
                'tenantId' => $this->params->idPath(),
            ],
        ];
    }

    public function getLoadBalancers(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/loadbalancers',
            'params' => [],
        ];
    }

    public function getLoadBalancer(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/loadbalancers/{id}',
            'params' => [],
        ];
    }

    public function postLoadBalancer(): array
    {
        return [
            'method'  => 'POST',
            'path'    => $this->pathPrefix.'/lbaas/loadbalancers',
            'jsonKey' => 'loadbalancer',
            'params'  => [
                'name'         => $this->params->name('loadbalancer'),
                'description'  => $this->params->descriptionJson(),
                'tenantId'     => $this->params->tenantId(),
                'vipSubnetId'  => $this->params->vipSubnetId(),
                'vipAddress'   => $this->params->vipAddress(),
                'adminStateUp' => $this->params->adminStateUp(),
                'provider'     => $this->params->provider(),
            ],
        ];
    }

    public function putLoadBalancer(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/lbaas/loadbalancers/{id}',
            'jsonKey' => 'loadbalancer',
            'params'  => [
                'id'           => $this->params->idPath(),
                'name'         => $this->params->name('loadbalancer'),
                'description'  => $this->params->descriptionJson(),
                'AdminStateUp' => $this->params->adminStateUp(),
            ],
        ];
    }

    public function deleteLoadBalancer(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/lbaas/loadbalancers/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }

    public function getLoadBalancerListeners(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/listeners',
            'params' => [],
        ];
    }

    public function getLoadBalancerListener(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/listeners/{id}',
            'params' => [],
        ];
    }

    public function postLoadBalancerListener(): array
    {
        return [
            'method'  => 'POST',
            'path'    => $this->pathPrefix.'/lbaas/listeners',
            'jsonKey' => 'listener',
            'params'  => [
                'name'            => $this->params->name('listener'),
                'description'     => $this->params->descriptionJson(),
                'loadbalancerId'  => $this->params->loadbalancerId(),
                'protocol'        => $this->params->protocol(),
                'protocolPort'    => $this->params->protocolPort(),
                'tenantId'        => $this->params->tenantId(),
                'adminStateUp'    => $this->params->adminStateUp(),
                'connectionLimit' => $this->params->connectionLimit(),
            ],
        ];
    }

    public function putLoadBalancerListener(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/lbaas/listeners/{id}',
            'jsonKey' => 'listener',
            'params'  => [
                'id'              => $this->params->idPath(),
                'name'            => $this->params->name('listener'),
                'description'     => $this->params->descriptionJson(),
                'adminStateUp'    => $this->params->adminStateUp(),
                'connectionLimit' => $this->params->connectionLimit(),
            ],
        ];
    }

    public function deleteLoadBalancerListener(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/lbaas/listeners/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }

    public function getLoadBalancerPools(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/pools',
            'params' => [],
        ];
    }

    public function getLoadBalancerPool(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/pools/{id}',
            'params' => [],
        ];
    }

    public function postLoadBalancerPool(): array
    {
        return [
            'method'  => 'POST',
            'path'    => $this->pathPrefix.'/lbaas/pools',
            'jsonKey' => 'pool',
            'params'  => [
                'name'               => $this->params->name('pool'),
                'description'        => $this->params->descriptionJson(),
                'adminStateUp'       => $this->params->adminStateUp(),
                'protocol'           => $this->params->protocol(),
                'lbAlgorithm'        => $this->params->lbAlgorithm(),
                'listenerId'         => $this->params->listenerId(),
                'sessionPersistence' => $this->params->sessionPersistence(),
            ],
        ];
    }

    public function putLoadBalancerPool(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/lbaas/pools/{id}',
            'jsonKey' => 'pool',
            'params'  => [
                'id'                 => $this->params->idPath(),
                'name'               => $this->params->name('pool'),
                'description'        => $this->params->descriptionJson(),
                'adminStateUp'       => $this->params->adminStateUp(),
                'lbAlgorithm'        => $this->params->lbAlgorithm(),
                'sessionPersistence' => $this->params->sessionPersistence(),
            ],
        ];
    }

    public function deleteLoadBalancerPool(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/lbaas/pools/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }

    public function getLoadBalancerMembers(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/pools/{poolId}/members',
            'params' => [
              'poolId' => $this->params->poolId(),
            ],
        ];
    }

    public function getLoadBalancerMember(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/pools/{poolId}/members/{id}',
            'params' => [
              'id'     => $this->params->idPath('member'),
              'poolId' => $this->params->poolId(),
            ],
        ];
    }

    public function postLoadBalancerMember(): array
    {
        return [
            'method'  => 'POST',
            'path'    => $this->pathPrefix.'/lbaas/pools/{poolId}/members',
            'jsonKey' => 'member',
            'params'  => [
                'poolId'       => $this->params->poolId(),
                'address'      => $this->params->address(),
                'protocolPort' => $this->params->protocolPort(),
                'adminStateUp' => $this->params->adminStateUp(),
                'weight'       => $this->params->weight(),
                'subnetId'     => $this->params->subnetId(),
            ],
        ];
    }

    public function putLoadBalancerMember(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/lbaas/pools/{poolId}/members/{id}',
            'jsonKey' => 'member',
            'params'  => [
                'poolId'       => $this->params->poolId(),
                'id'           => $this->params->idPath(),
                'weight'       => $this->params->weight(),
                'adminStateUp' => $this->params->adminStateUp(),
            ],
        ];
    }

    public function deleteLoadBalancerMember(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/lbaas/pools/{poolId}/members/{id}',
            'params' => [
                'poolId' => $this->params->poolId(),
                'id'     => $this->params->idPath(),
            ],
        ];
    }

    public function getLoadBalancerStats(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/loadbalancers/{loadbalancerId}/stats',
            'params' => [
              'loadbalancerId' => $this->params->loadBalancerIdUrl(),
            ],
        ];
    }

    public function getLoadBalancerStatuses(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/loadbalancers/{loadbalancerId}/statuses',
            'params' => [
              'loadbalancerId' => $this->params->loadBalancerIdUrl(),
            ],
        ];
    }

    public function getLoadBalancerHealthMonitors(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/healthmonitors',
            'params' => [],
        ];
    }

    public function getLoadBalancerHealthMonitor(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/lbaas/healthmonitors/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }

    public function postLoadBalancerHealthMonitor(): array
    {
        return [
            'method'  => 'POST',
            'path'    => $this->pathPrefix.'/lbaas/healthmonitors',
            'jsonKey' => 'healthmonitor',
            'params'  => [
                'type'          => $this->params->type(),
                'delay'         => $this->params->delay(),
                'timeout'       => $this->params->timeout(),
                'maxRetries'    => $this->params->maxRetries(),
                'poolId'        => $this->params->poolIdJson(),
                'tenantId'      => $this->params->tenantId(),
                'adminStateUp'  => $this->params->adminStateUp(),
                'httpMethod'    => $this->params->httpMethod(),
                'urlPath'       => $this->params->urlPath(),
                'expectedCodes' => $this->params->expectedCodes(),
            ],
        ];
    }

    public function putLoadBalancerHealthMonitor(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/lbaas/healthmonitors/{id}',
            'jsonKey' => 'healthmonitor',
            'params'  => [
                'id'            => $this->params->idPath(),
                'delay'         => $this->params->delay(),
                'timeout'       => $this->params->timeout(),
                'adminStateUp'  => $this->params->adminStateUp(),
                'maxRetries'    => $this->params->maxRetries(),
                'httpMethod'    => $this->params->httpMethod(),
                'urlPath'       => $this->params->urlPath(),
                'expectedCodes' => $this->params->expectedCodes(),
            ],
        ];
    }

    public function deleteLoadBalancerHealthMonitor(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/lbaas/healthmonitors/{id}',
            'params' => [
            'id' => $this->params->idPath(),
            ],
        ];
    }
}
