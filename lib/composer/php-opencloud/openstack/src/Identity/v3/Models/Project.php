<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3\Models;

use OpenStack\Common\Error\BadResponseError;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;

/**
 * @property \OpenStack\Identity\v3\Api $api
 */
class Project extends OperatorResource implements Creatable, Retrievable, Listable, Updateable, Deletable
{
    /** @var string */
    public $domainId;

    /** @var string */
    public $parentId;

    /** @var bool */
    public $enabled;

    /** @var string */
    public $description;

    /** @var string */
    public $id;

    /** @var array */
    public $links;

    /** @var string */
    public $name;

    protected $aliases = [
        'domain_id' => 'domainId',
        'parent_id' => 'parentId',
    ];

    protected $resourceKey  = 'project';
    protected $resourcesKey = 'projects';

    /**
     * {@inheritdoc}
     *
     * @param array $data {@see \OpenStack\Identity\v3\Api::postProjects}
     */
    public function create(array $data): Creatable
    {
        $response = $this->execute($this->api->postProjects(), $data);
        $this->populateFromResponse($response);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->executeWithState($this->api->getProject());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->patchProject());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteProject());
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::getProjectUserRoles}
     */
    public function listUserRoles(array $options = []): \Generator
    {
        $options['projectId'] = $this->id;

        return $this->model(Role::class)->enumerate($this->api->getProjectUserRoles(), $options);
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::putProjectUserRole}
     */
    public function grantUserRole(array $options)
    {
        $this->execute($this->api->putProjectUserRole(), ['projectId' => $this->id] + $options);
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::headProjectUserRole}
     */
    public function checkUserRole(array $options): bool
    {
        try {
            $this->execute($this->api->headProjectUserRole(), ['projectId' => $this->id] + $options);

            return true;
        } catch (BadResponseError $e) {
            return false;
        }
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::deleteProjectUserRole}
     */
    public function revokeUserRole(array $options)
    {
        $this->execute($this->api->deleteProjectUserRole(), ['projectId' => $this->id] + $options);
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::getProjectGroupRoles}
     */
    public function listGroupRoles(array $options = []): \Generator
    {
        $options['projectId'] = $this->id;

        return $this->model(Role::class)->enumerate($this->api->getProjectGroupRoles(), $options);
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::putProjectGroupRole}
     */
    public function grantGroupRole(array $options)
    {
        $this->execute($this->api->putProjectGroupRole(), ['projectId' => $this->id] + $options);
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::headProjectGroupRole}
     */
    public function checkGroupRole(array $options): bool
    {
        try {
            $this->execute($this->api->headProjectGroupRole(), ['projectId' => $this->id] + $options);

            return true;
        } catch (BadResponseError $e) {
            return false;
        }
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::deleteProjectGroupRole}
     */
    public function revokeGroupRole(array $options)
    {
        $this->execute($this->api->deleteProjectGroupRole(), ['projectId' => $this->id] + $options);
    }
}
