<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3\Models;

use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;

/**
 * @property \OpenStack\Identity\v3\Api $api
 */
class Role extends OperatorResource implements Creatable, Listable, Deletable
{
    /** @var string */
    public $id;

    /** @var string */
    public $name;

    /** @var array */
    public $links;

    protected $resourceKey  = 'role';
    protected $resourcesKey = 'roles';

    /**
     * {@inheritdoc}
     *
     * @param array $data {@see \OpenStack\Identity\v3\Api::postRoles}
     */
    public function create(array $data): Creatable
    {
        $response = $this->execute($this->api->postRoles(), $data);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteRole());
    }
}
