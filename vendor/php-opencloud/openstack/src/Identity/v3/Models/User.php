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
     * @param array $data {@see \OpenStack\Identity\v3\Api::postUsers}
     */
    public function create(array $data): Creatable
    {
        $response = $this->execute($this->api->postUsers(), $data);

        return $this->populateFromResponse($response);
    }

    public function retrieve()
    {
        $response = $this->execute($this->api->getUser(), ['id' => $this->id]);
        $this->populateFromResponse($response);
    }

    public function update()
    {
        $response = $this->executeWithState($this->api->patchUser());
        $this->populateFromResponse($response);
    }

    public function delete()
    {
        $this->execute($this->api->deleteUser(), ['id' => $this->id]);
    }

    /**
     * @return \Generator<mixed, \OpenStack\Identity\v3\Models\Group>
     */
    public function listGroups(): \Generator
    {
        return $this->model(Group::class)->enumerate($this->api->getUserGroups(), ['id' => $this->id]);
    }

    /**
     * @return \Generator<mixed, \OpenStack\Identity\v3\Models\Project>
     */
    public function listProjects(): \Generator
    {
        return $this->model(Project::class)->enumerate($this->api->getUserProjects(), ['id' => $this->id]);
    }

    /**
     * Creates a new application credential according to the provided options.
     *
     * @param array $options {@see \OpenStack\Identity\v3\Api::postApplicationCredential}
     */
    public function createApplicationCredential(array $options): ApplicationCredential
    {
        return $this->model(ApplicationCredential::class)->create(['userId' => $this->id] + $options);
    }

    /**
     * Retrieves an application credential object and populates its unique identifier object. This operation will not
     * perform a GET or HEAD request by default; you will need to call retrieve() if you want to pull in remote state
     * from the API.
     */
    public function getApplicationCredential(string $id): ApplicationCredential
    {
        return $this->model(ApplicationCredential::class, ['id' => $id, 'userId' => $this->id]);
    }
}
