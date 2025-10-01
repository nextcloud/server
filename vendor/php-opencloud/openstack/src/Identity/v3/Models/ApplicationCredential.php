<?php

namespace OpenStack\Identity\v3\Models;

use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;

/**
 * @property \OpenStack\Identity\v3\Api $api
 */
class ApplicationCredential extends OperatorResource implements Creatable, Listable, Retrievable, Deletable
{
    /** @var string */
    public $id;

    /** @var string */
    public $userId;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    /** @var string|null */
    public $secret = null;

    protected $aliases = [
        'user_id' => 'userId',
    ];

    protected $resourceKey  = 'application_credential';
    protected $resourcesKey = 'application_credentials';

    /**
     * {@inheritdoc}
     *
     * @param array $userOptions {@see \OpenStack\Identity\v3\Api::postApplicationCredential}
     */
    public function create(array $userOptions): Creatable
    {
        $response = $this->execute($this->api->postApplicationCredential(), $userOptions);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute(
            $this->api->getApplicationCredential(),
            ['id' => $this->id, 'userId' => $this->userId]
        );
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteApplicationCredential());
    }
}
