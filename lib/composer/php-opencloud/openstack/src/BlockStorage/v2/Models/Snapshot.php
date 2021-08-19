<?php

declare(strict_types=1);

namespace OpenStack\BlockStorage\v2\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\HasMetadata;
use OpenStack\Common\Resource\HasWaiterTrait;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;
use OpenStack\Common\Transport\Utils;
use Psr\Http\Message\ResponseInterface;

/**
 * @property \OpenStack\BlockStorage\v2\Api $api
 */
class Snapshot extends OperatorResource implements Listable, Creatable, Updateable, Deletable, Retrievable, HasMetadata
{
    use HasWaiterTrait;

    /** @var string */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $status;

    /** @var string */
    public $description;

    /** @var \DateTimeImmutable */
    public $createdAt;

    /** @var array */
    public $metadata = [];

    /** @var string */
    public $volumeId;

    /** @var int */
    public $size;

    /** @var string */
    public $projectId;

    protected $resourceKey  = 'snapshot';
    protected $resourcesKey = 'snapshots';
    protected $markerKey    = 'id';

    protected $aliases = [
        'volume_id'                                  => 'volumeId',
        'os-extended-snapshot-attributes:project_id' => 'projectId',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'created_at' => new Alias('createdAt', \DateTimeImmutable::class),
        ];
    }

    public function populateFromResponse(ResponseInterface $response): self
    {
        parent::populateFromResponse($response);
        $this->metadata = $this->parseMetadata($response);

        return $this;
    }

    public function retrieve()
    {
        $response = $this->executeWithState($this->api->getSnapshot());
        $this->populateFromResponse($response);
    }

    /**
     * @param array $userOptions {@see \OpenStack\BlockStorage\v2\Api::postSnapshots}
     */
    public function create(array $userOptions): Creatable
    {
        $response = $this->execute($this->api->postSnapshots(), $userOptions);

        return $this->populateFromResponse($response);
    }

    public function update()
    {
        $this->executeWithState($this->api->putSnapshot());
    }

    public function delete()
    {
        $this->executeWithState($this->api->deleteSnapshot());
    }

    public function getMetadata(): array
    {
        $response       = $this->executeWithState($this->api->getSnapshotMetadata());
        $this->metadata = $this->parseMetadata($response);

        return $this->metadata;
    }

    public function mergeMetadata(array $metadata)
    {
        $this->getMetadata();
        $this->metadata = array_merge($this->metadata, $metadata);
        $this->executeWithState($this->api->putSnapshotMetadata());
    }

    public function resetMetadata(array $metadata)
    {
        $this->metadata = $metadata;
        $this->executeWithState($this->api->putSnapshotMetadata());
    }

    public function parseMetadata(ResponseInterface $response): array
    {
        $json = Utils::jsonDecode($response);

        return isset($json['metadata']) ? $json['metadata'] : [];
    }
}
