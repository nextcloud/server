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
class Domain extends OperatorResource implements Creatable, Listable, Retrievable, Updateable, Deletable
{
    /** @var string */
    public $id;

    /** @var string */
    public $name;

    /** @var array */
    public $links;

    /** @var bool */
    public $enabled;

    /** @var string */
    public $description;

    protected $resourceKey  = 'domain';
    protected $resourcesKey = 'domains';

    /**
     * {@inheritdoc}
     *
     * @param array $data {@see \OpenStack\Identity\v3\Api::postDomains}
     */
    public function create(array $data): Creatable
    {
        $response = $this->execute($this->api->postDomains(), $data);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->executeWithState($this->api->getDomain());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->patchDomain());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteDomain());
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::getUserRoles}
     */
    public function listUserRoles(array $options = []): \Generator
    {
        $options['domainId'] = $this->id;

        return $this->model(Role::class)->enumerate($this->api->getUserRoles(), $options);
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::putUserRoles}
     */
    public function grantUserRole(array $options = [])
    {
        $this->execute($this->api->putUserRoles(), ['domainId' => $this->id] + $options);
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::headUserRole}
     */
    public function checkUserRole(array $options = []): bool
    {
        try {
            $this->execute($this->api->headUserRole(), ['domainId' => $this->id] + $options);

            return true;
        } catch (BadResponseError $e) {
            return false;
        }
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::deleteUserRole}
     */
    public function revokeUserRole(array $options = [])
    {
        $this->execute($this->api->deleteUserRole(), ['domainId' => $this->id] + $options);
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::getGroupRoles}
     */
    public function listGroupRoles(array $options = []): \Generator
    {
        $options['domainId'] = $this->id;

        return $this->model(Role::class)->enumerate($this->api->getGroupRoles(), $options);
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::putGroupRole}
     */
    public function grantGroupRole(array $options = [])
    {
        $this->execute($this->api->putGroupRole(), ['domainId' => $this->id] + $options);
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::headGroupRole}
     */
    public function checkGroupRole(array $options = []): bool
    {
        try {
            $this->execute($this->api->headGroupRole(), ['domainId' => $this->id] + $options);

            return true;
        } catch (BadResponseError $e) {
            return false;
        }
    }

    /**
     * @param array $options {@see \OpenStack\Identity\v3\Api::deleteGroupRole}
     */
    public function revokeGroupRole(array $options = [])
    {
        $this->execute($this->api->deleteGroupRole(), ['domainId' => $this->id] + $options);
    }
}
