<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2\Models;

use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;

/**
 * Represents a Compute v2 Quota Set.
 *
 * @property \OpenStack\Compute\v2\Api $api
 */
class QuotaSet extends OperatorResource implements Retrievable, Updateable, Deletable
{
    /**
     * The number of allowed instance cores for each tenant.
     *
     * @var int|array
     */
    public $cores;

    /**
     * The number of allowed fixed IP addresses for each tenant.
     * Must be equal to or greater than the number of allowed instances.
     *
     * @deprecated Since Nova v2.35. This attribute will eventually move to Neutron, it is advised you do not use this.
     *
     * @var int|object
     */
    public $fixedIps;

    /**
     * The number of allowed floating IP addresses for each tenant.
     *
     * @deprecated Since Nova v2.35. This attribute will eventually move to Neutron, it is advised you do not use this.
     *
     * @var int|array
     */
    public $floatingIps;

    /**
     * The UUID of the tenant/user the quotas listed for.
     *
     * @var string
     */
    public $tenantId;

    /**
     * The number of allowed bytes of content for each injected file.
     *
     * @var int|array
     */
    public $injectedFileContentBytes;

    /**
     * The number of allowed bytes for each injected file path.
     *
     * @var int|array
     */
    public $injectedFilePathBytes;

    /**
     * The number of allowed injected files for each tenant.
     *
     * @var int|array
     */
    public $injectedFiles;

    /**
     * The number of allowed instances for each tenant.
     *
     * @var int|array
     */
    public $instances;

    /**
     * The number of allowed key pairs for each user.
     *
     * @var int|array
     */
    public $keyPairs;

    /**
     * The number of allowed metadata items for each instance.
     *
     * @var int|array
     */
    public $metadataItems;

    /**
     * The amount of allowed instance RAM, in MB, for each tenant.
     *
     * @var int|array
     */
    public $ram;

    /**
     * The number of allowed rules for each security group.
     *
     * @deprecated Since Nova v2.35. This attribute will eventually move to Neutron, it is advised you do not use this.
     *
     * @var int|array
     */
    public $securityGroupRules;

    /**
     * The number of allowed security groups for each tenant.
     *
     * @deprecated Since Nova v2.35. This attribute will eventually move to Neutron, it is advised you do not use this.
     *
     * @var int|array
     */
    public $securityGroups;

    /**
     * The number of allowed server groups for each tenant.
     *
     * @var int|array
     */
    public $serverGroups;

    /**
     * The number of allowed members for each server group.
     *
     * @var int|object
     */
    public $serverGroupMembers;

    protected $resourceKey = 'quota_set';

    protected $aliases = [
        'id'                          => 'tenantId',
        'fixed_ips'                   => 'fixedIps',
        'floating_ips'                => 'floatingIps',
        'injected_file_content_bytes' => 'injectedFileContentBytes',
        'injected_file_path_bytes'    => 'injectedFilePathBytes',
        'injected_files'              => 'injectedFiles',
        'key_pairs'                   => 'keyPairs',
        'metadata_items'              => 'metadataItems',
        'security_group_rules'        => 'securityGroupRules',
        'security_groups'             => 'securityGroups',
        'server_group_members'        => 'serverGroupMembers',
        'server_groups'               => 'serverGroups',
    ];

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getQuotaSet(), ['tenantId' => (string) $this->tenantId]);
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $response = $this->executeWithState($this->api->deleteQuotaSet());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->putQuotaSet());
        $this->populateFromResponse($response);
    }
}
