<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3\Models;

use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;

/**
 * @property \OpenStack\Identity\v3\Api $api
 */
class Endpoint extends OperatorResource implements Creatable, Updateable, Deletable, Retrievable
{
    /** @var string */
    public $id;

    /** @var string */
    public $interface;

    /** @var string */
    public $name;

    /** @var string */
    public $serviceId;

    /** @var string */
    public $region;

    /** @var array */
    public $links;

    /** @var string */
    public $url;

    protected $resourceKey  = 'endpoint';
    protected $resourcesKey = 'endpoints';
    protected $aliases      = ['service_id' => 'serviceId'];

    /**
     * {@inheritdoc}
     *
     * @param array $data {@see \OpenStack\Identity\v3\Api::postEndpoints}
     */
    public function create(array $data): Creatable
    {
        $response = $this->execute($this->api->postEndpoints(), $data);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->executeWithState($this->api->getEndpoint());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->patchEndpoint());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->execute($this->api->deleteEndpoint(), $this->getAttrs(['id']));
    }

    public function regionMatches(string $value): bool
    {
        return in_array($this->region, ['*', $value]);
    }

    public function interfaceMatches(string $value): bool
    {
        return $this->interface && $this->interface == $value;
    }
}
