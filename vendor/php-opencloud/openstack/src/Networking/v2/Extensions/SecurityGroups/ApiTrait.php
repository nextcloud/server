<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2\Extensions\SecurityGroups;

/**
 * @property \OpenStack\Networking\v2\Params $params
 * @property string                          $pathPrefix
 *
 * @internal
 */
trait ApiTrait
{
    /**
     * Returns information about GET security-groups/{security_group_id} HTTP
     * operation.
     */
    public function getSecurityGroups(): array
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
     */
    public function postSecurityGroups(): array
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
     */
    public function putSecurityGroups(): array
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
     */
    public function getSecurityGroup(): array
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
     */
    public function deleteSecurityGroup(): array
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
     */
    public function getSecurityRules(): array
    {
        return [
            'method' => 'GET',
            'path'   => $this->pathPrefix.'/security-group-rules',
            'params' => [],
        ];
    }

    /**
     * Returns information about POST security-group-rules HTTP operation.
     */
    public function postSecurityRules(): array
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
     */
    public function deleteSecurityRule(): array
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
     */
    public function getSecurityRule(): array
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
