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
class User extends OperatorResource implements Creatable, Listable, Retrievable, Updateable, Deletable
{
    /** @var string */
    public $domainId;

    /** @var string */
    public $defaultProjectId;

    /** @var string */
    public $id;

    /** @var string */
    public $email;

    /** @var bool */
    public $enabled;

    /** @var string */
    public $description;

    /** @var array */
    public $links;

    /** @var string */
    public $name;

    protected $aliases = [
        'domain_id'          => 'domainId',
        'default_project_id' => 'defaultProjectId',
    ];

    protected $resourceKey  = 'user';
    protected $resourcesKey = 'users';

    /**
     * {@inheritdoc}
     *
     * @param array $data {@see \OpenStack\Identity\v3\Api::postUsers}
     */
    public function create(array $data): Creatable
    {
        $response = $this->execute($this->api->postUsers(), $data);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getUser(), ['id' => $this->id]);
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->patchUser());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->execute($this->api->deleteUser(), ['id' => $this->id]);
    }

    public function listGroups(): \Generator
    {
        $options['id'] = $this->id;

        return $this->model(Group::class)->enumerate($this->api->getUserGroups(), $options);
    }

    public function listProjects(): \Generator
    {
        return $this->model(Project::class)->enumerate($this->api->getUserProjects(), ['id' => $this->id]);
    }
}
