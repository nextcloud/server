<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3\Models;

use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;

/**
 * @property \OpenStack\Identity\v3\Api $api
 */
class Credential extends OperatorResource implements Creatable, Updateable, Retrievable, Listable, Deletable
{
    /** @var string */
    public $blob;

    /** @var string */
    public $id;

    /** @var array */
    public $links;

    /** @var string */
    public $projectId;

    /** @var string */
    public $type;

    /** @var string */
    public $userId;

    protected $aliases = [
        'project_id' => 'projectId',
        'user_id'    => 'userId',
    ];

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Creatable
    {
        $response = $this->execute($this->api->postCredentials(), $data);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->executeWithState($this->api->getCredential());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->patchCredential());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteCredential());
    }
}
