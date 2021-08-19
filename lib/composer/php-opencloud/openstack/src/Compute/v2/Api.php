<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2;

use OpenStack\Common\Api\AbstractApi;

/**
 * A representation of the Compute (Nova) v2 REST API.
 *
 * @internal
 */
class Api extends AbstractApi
{
    public function __construct()
    {
        $this->params = new Params();
    }

    public function getLimits(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'limits',
            'params' => [],
        ];
    }

    public function getFlavors(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'flavors',
            'params' => [
                'limit'   => $this->params->limit(),
                'marker'  => $this->params->marker(),
                'minDisk' => $this->params->minDisk(),
                'minRam'  => $this->params->minRam(),
            ],
        ];
    }

    public function getFlavorsDetail(): array
    {
        $op = $this->getFlavors();
        $op['path'] .= '/detail';

        return $op;
    }

    public function getFlavor(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'flavors/{id}',
            'params' => ['id' => $this->params->urlId('flavor')],
        ];
    }

    public function postFlavors(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'flavors',
            'jsonKey' => 'flavor',
            'params'  => [
                'id'    => $this->notRequired($this->params->id('flavor')),
                'name'  => $this->isRequired($this->params->name('flavor')),
                'ram'   => $this->params->flavorRam(),
                'vcpus' => $this->params->flavorVcpus(),
                'swap'  => $this->params->flavorSwap(),
                'disk'  => $this->params->flavorDisk(),
            ],
        ];
    }

    public function deleteFlavor(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'flavors/{id}',
            'params' => [
                'id' => $this->params->idPath(),
            ],
        ];
    }

    public function getImages(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'images',
            'params' => [
                'limit'        => $this->params->limit(),
                'marker'       => $this->params->marker(),
                'name'         => $this->params->flavorName(),
                'changesSince' => $this->params->filterChangesSince('image'),
                'server'       => $this->params->flavorServer(),
                'status'       => $this->params->filterStatus('image'),
                'type'         => $this->params->flavorType(),
            ],
        ];
    }

    public function getImagesDetail(): array
    {
        $op = $this->getImages();
        $op['path'] .= '/detail';

        return $op;
    }

    public function getImage(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'images/{id}',
            'params' => ['id' => $this->params->urlId('image')],
        ];
    }

    public function deleteImage(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'images/{id}',
            'params' => ['id' => $this->params->urlId('image')],
        ];
    }

    public function getImageMetadata(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'images/{id}/metadata',
            'params' => ['id' => $this->params->urlId('image')],
        ];
    }

    public function putImageMetadata(): array
    {
        return [
            'method' => 'PUT',
            'path'   => 'images/{id}/metadata',
            'params' => [
                'id'       => $this->params->urlId('image'),
                'metadata' => $this->params->metadata(),
            ],
        ];
    }

    public function postImageMetadata(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'images/{id}/metadata',
            'params' => [
                'id'       => $this->params->urlId('image'),
                'metadata' => $this->params->metadata(),
            ],
        ];
    }

    public function getImageMetadataKey(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'images/{id}/metadata/{key}',
            'params' => [
                'id'  => $this->params->urlId('image'),
                'key' => $this->params->key(),
            ],
        ];
    }

    public function deleteImageMetadataKey(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'images/{id}/metadata/{key}',
            'params' => [
                'id'  => $this->params->urlId('image'),
                'key' => $this->params->key(),
            ],
        ];
    }

    public function postServer(): array
    {
        return [
            'path'    => 'servers',
            'method'  => 'POST',
            'jsonKey' => 'server',
            'params'  => [
                'imageId'            => $this->notRequired($this->params->imageId()),
                'flavorId'           => $this->params->flavorId(),
                'personality'        => $this->params->personality(),
                'metadata'           => $this->notRequired($this->params->metadata()),
                'name'               => $this->isRequired($this->params->name('server')),
                'securityGroups'     => $this->params->securityGroups(),
                'userData'           => $this->params->userData(),
                'availabilityZone'   => $this->params->availabilityZone(),
                'networks'           => $this->params->networks(),
                'blockDeviceMapping' => $this->params->blockDeviceMapping(),
                'keyName'            => $this->params->keyName(),
            ],
        ];
    }

    public function getServers(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'servers',
            'params' => [
                'limit'        => $this->params->limit(),
                'marker'       => $this->params->marker(),
                'changesSince' => $this->params->filterChangesSince('server'),
                'imageId'      => $this->params->filterImage(),
                'flavorId'     => $this->params->filterFlavor(),
                'name'         => $this->params->filterName(),
                'status'       => $this->params->filterStatus('server'),
                'host'         => $this->params->filterHost(),
                'allTenants'   => $this->params->allTenants(),
            ],
        ];
    }

    public function getServersDetail(): array
    {
        $definition = $this->getServers();
        $definition['path'] .= '/detail';

        return $definition;
    }

    public function getServer(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'servers/{id}',
            'params' => [
                'id' => $this->params->urlId('server'),
            ],
        ];
    }

    public function putServer(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => 'servers/{id}',
            'jsonKey' => 'server',
            'params'  => [
                'id'   => $this->params->urlId('server'),
                'ipv4' => $this->params->ipv4(),
                'ipv6' => $this->params->ipv6(),
                'name' => $this->params->name('server'),
            ],
        ];
    }

    public function deleteServer(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'servers/{id}',
            'params' => ['id' => $this->params->urlId('server')],
        ];
    }

    public function changeServerPassword(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'changePassword',
            'params'  => [
                'id'       => $this->params->urlId('server'),
                'password' => $this->params->password(),
            ],
        ];
    }

    public function resetServerState(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'servers/{id}/action',
            'params' => [
                'id'         => $this->params->urlId('server'),
                'resetState' => $this->params->resetState(),
            ],
        ];
    }

    public function rebootServer(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'reboot',
            'params'  => [
                'id'   => $this->params->urlId('server'),
                'type' => $this->params->rebootType(),
            ],
        ];
    }

    public function startServer(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'servers/{id}/action',
            'params' => [
                'id'       => $this->params->urlId('server'),
                'os-start' => $this->params->nullAction(),
            ],
        ];
    }

    public function stopServer(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'servers/{id}/action',
            'params' => [
                'id'      => $this->params->urlId('server'),
                'os-stop' => $this->params->nullAction(),
            ],
        ];
    }

    public function rebuildServer(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'rebuild',
            'params'  => [
                'id'          => $this->params->urlId('server'),
                'ipv4'        => $this->params->ipv4(),
                'ipv6'        => $this->params->ipv6(),
                'imageId'     => $this->params->imageId(),
                'personality' => $this->params->personality(),
                'name'        => $this->params->name('server'),
                'metadata'    => $this->notRequired($this->params->metadata()),
                'adminPass'   => $this->params->password(),
            ],
        ];
    }

    public function rescueServer(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'rescue',
            'params'  => [
                'id'        => $this->params->urlId('server'),
                'imageId'   => $this->params->rescueImageId(),
                'adminPass' => $this->notRequired($this->params->password()),
            ],
        ];
    }

    public function unrescueServer(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'servers/{id}/action',
            'params' => [
                'id'       => $this->params->urlId('server'),
                'unrescue' => $this->params->nullAction(),
            ],
        ];
    }

    public function resizeServer(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'resize',
            'params'  => [
                'id'       => $this->params->urlId('server'),
                'flavorId' => $this->params->flavorId(),
            ],
        ];
    }

    public function confirmServerResize(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'servers/{id}/action',
            'params' => [
                'id'            => $this->params->urlId('server'),
                'confirmResize' => $this->params->nullAction(),
            ],
        ];
    }

    public function revertServerResize(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'servers/{id}/action',
            'params' => [
                'id'           => $this->params->urlId('server'),
                'revertResize' => $this->params->nullAction(),
            ],
        ];
    }

    public function getConsoleOutput(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'os-getConsoleOutput',
            'params'  => [
                'id'     => $this->params->urlId('server'),
                'length' => $this->notRequired($this->params->consoleLogLength()),
            ],
        ];
    }

    public function getAllConsoleOutput(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'servers/{id}/action',
            'params' => [
                'id'                  => $this->params->urlId('server'),
                'os-getConsoleOutput' => $this->params->emptyObject(),
            ],
        ];
    }

    public function createServerImage(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'createImage',
            'params'  => [
                'id'       => $this->params->urlId('server'),
                'metadata' => $this->notRequired($this->params->metadata()),
                'name'     => $this->isRequired($this->params->name('server')),
            ],
        ];
    }

    public function getVncConsole(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'os-getVNCConsole',
            'params'  => [
                'id'   => $this->params->urlId('server'),
                'type' => $this->params->consoleType(),
            ],
        ];
    }

    public function getSpiceConsole(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'os-getSPICEConsole',
            'params'  => [
                'id'   => $this->params->urlId('server'),
                'type' => $this->params->consoleType(),
            ],
        ];
    }

    public function getSerialConsole(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'os-getSerialConsole',
            'params'  => [
                'id'   => $this->params->urlId('server'),
                'type' => $this->params->consoleType(),
            ],
        ];
    }

    public function getRDPConsole(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'os-getRDPConsole',
            'params'  => [
                'id'   => $this->params->urlId('server'),
                'type' => $this->params->consoleType(),
            ],
        ];
    }

    public function getAddresses(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'servers/{id}/ips',
            'params' => ['id' => $this->params->urlId('server')],
        ];
    }

    public function getAddressesByNetwork(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'servers/{id}/ips/{networkLabel}',
            'params' => [
                'id'           => $this->params->urlId('server'),
                'networkLabel' => $this->params->networkLabel(),
            ],
        ];
    }

    public function getInterfaceAttachments(): array
    {
        return [
            'method'  => 'GET',
            'path'    => 'servers/{id}/os-interface',
            'jsonKey' => 'interfaceAttachments',
            'params'  => [
                'id' => $this->params->urlId('server'),
            ],
        ];
    }

    public function getInterfaceAttachment(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'servers/{id}/os-interface/{portId}',
            'params' => [
                'id'     => $this->params->urlId('server'),
                'portId' => $this->params->portId(),
            ],
        ];
    }

    public function postInterfaceAttachment(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/os-interface',
            'jsonKey' => 'interfaceAttachment',
            'params'  => [
                'id'               => $this->params->urlId('server'),
                'portId'           => $this->notRequired($this->params->portId()),
                'networkId'        => $this->notRequired($this->params->networkId()),
                'fixedIpAddresses' => $this->notRequired($this->params->fixedIpAddresses()),
                'tag'              => $this->notRequired($this->params->tag()),
            ],
        ];
    }

    public function deleteInterfaceAttachment(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'servers/{id}/os-interface/{portId}',
            'params' => [
                'id'     => $this->params->urlId('image'),
                'portId' => $this->params->portId(),
            ],
        ];
    }

    public function getServerMetadata(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'servers/{id}/metadata',
            'params' => ['id' => $this->params->urlId('server')],
        ];
    }

    public function putServerMetadata(): array
    {
        return [
            'method' => 'PUT',
            'path'   => 'servers/{id}/metadata',
            'params' => [
                'id'       => $this->params->urlId('server'),
                'metadata' => $this->params->metadata(),
            ],
        ];
    }

    public function postServerMetadata(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'servers/{id}/metadata',
            'params' => [
                'id'       => $this->params->urlId('server'),
                'metadata' => $this->params->metadata(),
            ],
        ];
    }

    public function getServerMetadataKey(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'servers/{id}/metadata/{key}',
            'params' => [
                'id'  => $this->params->urlId('server'),
                'key' => $this->params->key(),
            ],
        ];
    }

    public function deleteServerMetadataKey(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'servers/{id}/metadata/{key}',
            'params' => [
                'id'  => $this->params->urlId('server'),
                'key' => $this->params->key(),
            ],
        ];
    }

    public function getKeypair(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'os-keypairs/{name}',
            'params' => [
                'name'   => $this->isRequired($this->params->keypairName()),
                'userId' => $this->params->userId(),
            ],
        ];
    }

    public function getKeypairs(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'os-keypairs',
            'params' => [
                'userId' => $this->params->userId(),
            ],
        ];
    }

    public function postKeypair(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'os-keypairs',
            'jsonKey' => 'keypair',
            'params'  => [
                'name'      => $this->isRequired($this->params->name('keypair')),
                'publicKey' => $this->params->keypairPublicKey(),
                'type'      => $this->params->keypairType(),
                'userId'    => $this->params->keypairUserId(),
            ],
        ];
    }

    public function deleteKeypair(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'os-keypairs/{name}',
            'params' => [
                'name' => $this->isRequired($this->params->keypairName()),
            ],
        ];
    }

    public function postSecurityGroup(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'addSecurityGroup',
            'params'  => [
                'id'   => $this->params->urlId('server'),
                'name' => $this->isRequired($this->params->name('securityGroup')),
            ],
        ];
    }

    public function deleteSecurityGroup(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/action',
            'jsonKey' => 'removeSecurityGroup',
            'params'  => [
                'id'   => $this->params->urlId('server'),
                'name' => $this->isRequired($this->params->name('securityGroup')),
            ],
        ];
    }

    public function getSecurityGroups(): array
    {
        return [
            'method'  => 'GET',
            'path'    => 'servers/{id}/os-security-groups',
            'jsonKey' => 'security_groups',
            'params'  => [
                'id' => $this->params->urlId('server'),
            ],
        ];
    }

    public function getVolumeAttachments(): array
    {
        return [
            'method'  => 'GET',
            'path'    => 'servers/{id}/os-volume_attachments',
            'jsonKey' => 'volumeAttachments',
            'params'  => [
                'id' => $this->params->urlId('server'),
            ],
        ];
    }

    public function postVolumeAttachments(): array
    {
        return [
            'method'  => 'POST',
            'path'    => 'servers/{id}/os-volume_attachments',
            'jsonKey' => 'volumeAttachment',
            'params'  => [
                'id'       => $this->params->urlId('server'),
                'volumeId' => $this->params->volumeId(),
            ],
        ];
    }

    public function deleteVolumeAttachments(): array
    {
        return [
            'method' => 'DELETE',
            'path'   => 'servers/{id}/os-volume_attachments/{attachmentId}',
            'params' => [
                'id'           => $this->params->urlId('server'),
                'attachmentId' => $this->params->attachmentId(),
            ],
        ];
    }

    public function getHypervisorStatistics(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'os-hypervisors/statistics',
            'params' => [
            ],
        ];
    }

    public function getHypervisors(): array
    {
        return [
            'method'  => 'GET',
            'path'    => 'os-hypervisors',
            'jsonKey' => 'hypervisors',
            'params'  => [
                'limit'  => $this->params->limit(),
                'marker' => $this->params->marker(),
            ],
        ];
    }

    public function getHypervisorsDetail(): array
    {
        $definition = $this->getHypervisors();
        $definition['path'] .= '/detail';

        return $definition;
    }

    public function getHypervisor(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'os-hypervisors/{id}',
            'params' => ['id' => $this->params->urlId('id')],
        ];
    }

    public function getAvailabilityZones(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'os-availability-zone/detail',
            'params' => [
                'limit'  => $this->params->limit(),
                'marker' => $this->params->marker(),
            ],
        ];
    }

    public function getHosts(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'os-hosts',
            'params' => [
                'limit'  => $this->params->limit(),
                'marker' => $this->params->marker(),
            ],
        ];
    }

    public function getHost(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'os-hosts/{name}',
            'params' => ['name' => $this->params->urlId('name')],
        ];
    }

    public function getQuotaSet(): array
    {
        return [
            'method' => 'GET',
            'path'   => 'os-quota-sets/{tenantId}',
            'params' => [
                'tenantId' => $this->params->urlId('quota-sets'),
            ],
        ];
    }

    public function getQuotaSetDetail(): array
    {
        $data = $this->getQuotaSet();
        $data['path'] .= '/detail';

        return $data;
    }

    public function deleteQuotaSet(): array
    {
        return [
            'method'  => 'DELETE',
            'path'    => 'os-quota-sets/{tenantId}',
            'jsonKey' => 'quota_set',
            'params'  => [
                'tenantId' => $this->params->urlId('quota-sets'),
            ],
        ];
    }

    public function putQuotaSet(): array
    {
        return [
            'method'  => 'PUT',
            'path'    => 'os-quota-sets/{tenantId}',
            'jsonKey' => 'quota_set',
            'params'  => [
                'tenantId'                 => $this->params->idPath(),
                'force'                    => $this->notRequired($this->params->quotaSetLimitForce()),
                'instances'                => $this->notRequired($this->params->quotaSetLimitInstances()),
                'cores'                    => $this->notRequired($this->params->quotaSetLimitCores()),
                'fixedIps'                 => $this->notRequired($this->params->quotaSetLimitFixedIps()),
                'floatingIps'              => $this->notRequired($this->params->quotaSetLimitFloatingIps()),
                'injectedFileContentBytes' => $this->notRequired($this->params->quotaSetLimitInjectedFileContentBytes()),
                'injectedFilePathBytes'    => $this->notRequired($this->params->quotaSetLimitInjectedFilePathBytes()),
                'injectedFiles'            => $this->notRequired($this->params->quotaSetLimitInjectedFiles()),
                'keyPairs'                 => $this->notRequired($this->params->quotaSetLimitKeyPairs()),
                'metadataItems'            => $this->notRequired($this->params->quotaSetLimitMetadataItems()),
                'ram'                      => $this->notRequired($this->params->quotaSetLimitRam()),
                'securityGroupRules'       => $this->notRequired($this->params->quotaSetLimitSecurityGroupRules()),
                'securityGroups'           => $this->notRequired($this->params->quotaSetLimitSecurityGroups()),
                'serverGroups'             => $this->notRequired($this->params->quotaSetLimitServerGroups()),
                'serverGroupMembers'       => $this->notRequired($this->params->quotaSetLimitServerGroupMembers()),
            ],
        ];
    }
}
