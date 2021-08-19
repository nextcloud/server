<?php

declare(strict_types=1);

namespace OpenStack\Images\v2\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;

/**
 * @property \OpenStack\Images\v2\Api $api
 */
class Member extends OperatorResource implements Creatable, Listable, Retrievable, Deletable
{
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_PENDING  = 'pending';
    const STATUS_REJECTED = 'rejected';

    /** @var string */
    public $imageId;

    /** @var string */
    public $id;

    /** @var \DateTimeImmutable */
    public $createdAt;

    /** @var \DateTimeImmutable */
    public $updatedAt;

    /** @var string */
    public $schemaUri;

    /** @var string */
    public $status;

    protected $aliases = [
        'member_id' => 'id',
        'image_id'  => 'imageId',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'created_at' => new Alias('createdAt', \DateTimeImmutable::class),
            'updated_at' => new Alias('updatedAt', \DateTimeImmutable::class),
        ];
    }

    public function create(array $userOptions): Creatable
    {
        $response = $this->executeWithState($this->api->postImageMembers());

        return $this->populateFromResponse($response);
    }

    public function retrieve()
    {
        $response = $this->executeWithState($this->api->getImageMember());
        $this->populateFromResponse($response);
    }

    public function delete()
    {
        $this->executeWithState($this->api->deleteImageMember());
    }

    public function updateStatus($status)
    {
        $this->status = $status;
        $this->executeWithState($this->api->putImageMember());
    }
}
