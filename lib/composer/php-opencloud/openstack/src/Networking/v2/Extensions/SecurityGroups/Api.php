<?php

namespace OpenStack\Networking\v2\Extensions\SecurityGroups;

use OpenStack\Common\Api\AbstractApi;

class Api extends AbstractApi
{
    private $pathPrefix = 'v2.0';

    public function __construct()
    {
        $this->params = new Params();
    }

    /**
     * Returns information about GET security-groups/{security_group_id} HTTP
     * operation.
     *
     * @return array
     */
    public function getSecurityGroups()
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/security-groups',
            'params' => [
                'tenantId' => $this->params->queryTenantId(),
                'name'     => $this->params->filterName(),
            ],
        ];
    }

    /**
     * Returns information about POST security-groups HTTP operation.
     *
     * @return array
     */
    public function postSecurityGroups()
    {
        return [
            'method'  => 'POST',
            'path'    => $this->pathPrefix.'/security-groups',
            'jsonKey' => 'security_group',
            'params'  => [
                'description' => $this->params->descriptionJson(),
                'name'        => $this->params->nameJson(),
            ],
        ];
    }

    /**
     * Returns information about PUT security-groups HTTP operation.
     *
     * @return array
     */
    public function putSecurityGroups()
    {
        return [
            'method'  => 'PUT',
            'path'    => $this->pathPrefix.'/security-groups/{id}',
            'jsonKey' => 'security_group',
            'params'  => [
                'id'          => $this->params->idPath(),
                'description' => $this->params->descriptionJson(),
                'name'        => $this->params->nameJson(),
            ],
        ];
    }

    /**
     * Returns information about GET security-groups/{security_group_id} HTTP
     * operation.
     *
     * @return array
     */
    public function getSecurityGroup()
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/security-groups/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }

    /**
     * Returns information about DELETE security-groups/{security_group_id} HTTP
     * operation.
     *
     * @return array
     */
    public function deleteSecurityGroup()
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/security-groups/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }

    /**
     * Returns information about GET security-group-rules HTTP operation.
     *
     * @return array
     */
    public function getSecurityRules()
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/security-group-rules',
            'params' => [],
        ];
    }

    /**
     * Returns information about POST security-group-rules HTTP operation.
     *
     * @return array
     */
    public function postSecurityRules()
    {
        return [
            'method'  => 'POST',
            'path'    => $this->pathPrefix.'/security-group-rules',
            'jsonKey' => 'security_group_rule',
            'params'  => [
                'direction'       => $this->params->directionJson(),
                'ethertype'       => $this->params->ethertypeJson(),
                'securityGroupId' => $this->params->securityGroupIdJson(),
                'portRangeMin'    => $this->params->portRangeMinJson(),
                'portRangeMax'    => $this->params->portRangeMaxJson(),
                'protocol'        => $this->params->protocolJson(),
                'remoteGroupId'   => $this->params->remoteGroupIdJson(),
                'remoteIpPrefix'  => $this->params->remoteIpPrefixJson(),
                'tenantId'        => $this->params->tenantIdJson(),
            ],
        ];
    }

    /**
     * Returns information about DELETE
     * security-group-rules/{rules-security-groups-id} HTTP operation.
     *
     * @return array
     */
    public function deleteSecurityRule()
    {
        return [
            'method' => 'DELETE',
            'path'   => $this->pathPrefix.'/security-group-rules/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }

    /**
     * Returns information about GET
     * security-group-rules/{rules-security-groups-id} HTTP operation.
     *
     * @return array
     */
    public function getSecurityRule()
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/security-group-rules/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }
}
