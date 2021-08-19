<?php

declare(strict_types=1);

namespace OpenStack\Metric\v1\Gnocchi\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;

/**
 * @property Api $api
 */
class Metric extends OperatorResource implements Retrievable
{
    /** @var string */
    public $createdByUserId;

    /** @var resource */
    public $resource;

    /** @var string */
    public $name;

    /** @var string */
    public $createdByProjectId;

    /** @var array */
    public $archivePolicy;

    /** @var string */
    public $id;

    /** @var string */
    public $unit;

    protected $aliases = [
        'created_by_user_id'    => 'createdByUserId',
        'created_by_project_id' => 'createdByProjectId',
        'archive_policy'        => 'archivePolicy',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'resource' => new Alias('resource', Resource::class),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->executeWithState($this->api->getMetric());
        $this->populateFromResponse($response);
    }
}
