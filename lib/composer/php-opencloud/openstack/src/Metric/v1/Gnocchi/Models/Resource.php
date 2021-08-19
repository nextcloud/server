<?php

declare(strict_types=1);

namespace OpenStack\Metric\v1\Gnocchi\Models;

use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Transport\Utils;
use OpenStack\Metric\v1\Gnocchi\Api;

/**
 * @property Api $api
 */
class Resource extends OperatorResource implements Retrievable
{
    const RESOURCE_TYPE_GENERIC                    = 'generic';
    const RESOURCE_TYPE_CEPH_ACCOUNT               = 'ceph_account';
    const RESOURCE_TYPE_HOST                       = 'host';
    const RESOURCE_TYPE_HOST_DISK                  = 'host_disk';
    const RESOURCE_TYPE_HOST_NETWORK_INTERFACE     = 'host_network_interface';
    const RESOURCE_TYPE_IDENTITY                   = 'identity';
    const RESOURCE_TYPE_IMAGE                      = 'image';
    const RESOURCE_TYPE_INSTANCE                   = 'instance';
    const RESOURCE_TYPE_INSTANCE_DISK              = 'instance_disk';
    const RESOURCE_TYPE_INSTANCE_NETWORK_INTERFACE = 'instance_network_interface';
    const RESOURCE_TYPE_IPMI                       = 'ipmi';
    const RESOURCE_TYPE_NETWORK                    = 'network';
    const RESOURCE_TYPE_STACK                      = 'stack';
    const RESOURCE_TYPE_SWIFT_ACCOUNT              = 'swift_account';
    const RESOURCE_TYPE_VOLUME                     = 'volume';

    /** @var string */
    public $createdByUserId;

    /** @var string */
    public $startedAt;

    /** @var string */
    public $displayName;

    /** @var string */
    public $revisionEnd;

    /** @var string */
    public $userId;

    /** @var string */
    public $createdByProjectId;

    /** @var string */
    public $id;

    /** @var array */
    public $metrics;

    /** @var string */
    public $host;

    /** @var string */
    public $imageRef;

    /** @var string */
    public $flavorId;

    /** @var string */
    public $serverGroup;

    /** @var string */
    public $originalResourceId;

    /** @var string */
    public $revisionStart;

    /** @var string */
    public $projectId;

    /** @var string */
    public $type;

    /** @var string */
    public $endedAt;

    protected $aliases = [
        'created_by_user_id'    => 'createdByUserId',
        'started_at'            => 'startedAt',
        'display_name'          => 'displayName',
        'revision_end'          => 'revisionEnd',
        'user_id'               => 'userId',
        'created_by_project_id' => 'createdByProjectId',
        'image_ref'             => 'imageRef',
        'flavor_id'             => 'flavorId',
        'server_group'          => 'serverGroup',
        'original_resource_id'  => 'originalResourceId',
        'revision_start'        => 'revisionStart',
        'project_id'            => 'projectId',
        'ended_at'              => 'endedAt',
    ];

    public function retrieve()
    {
        $response = $this->execute($this->api->getResource(), ['type' => $this->type, 'id' => $this->id]);
        $this->populateFromResponse($response);
    }

    public function getMetric(string $metric): Metric
    {
        $response = $this->execute(
            $this->api->getResourceMetric(),
            [
                'resourceId' => $this->id,
                'metric'     => $metric,
                'type'       => $this->type,
            ]
        );
        $metric = $this->model(Metric::class)->populateFromResponse($response);

        return $metric;
    }

    /**
     * @param array $options {@see \OpenStack\Metric\v1\Gnocchi\Api::getResourceMetricMeasures}
     */
    public function getMetricMeasures(array $options = []): array
    {
        $options = array_merge(
            $options,
            [
                'resourceId' => $this->id,
                'type'       => $this->type,
            ]
        );

        $response = $this->execute($this->api->getResourceMetricMeasures(), $options);

        return Utils::jsonDecode($response);
    }

    /**
     * @param array $options {@see \OpenStack\Metric\v1\Gnocchi\Api::getResourceMetrics}
     */
    public function listResourceMetrics(array $options = []): \Generator
    {
        $options['resourceId'] = $this->id;

        return $this->model(Metric::class)->enumerate($this->api->getResourceMetrics(), $options);
    }
}
