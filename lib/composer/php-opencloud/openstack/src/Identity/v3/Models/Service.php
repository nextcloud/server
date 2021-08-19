<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Resource\Updateable;

/**
 * @property \OpenStack\Identity\v3\Api $api
 */
class Service extends OperatorResource implements Creatable, Listable, Retrievable, Updateable, Deletable
{
    /** @var string */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $type;

    /** @var string */
    public $description;

    /** @var []Endpoint */
    public $endpoints;

    /** @var array */
    public $links;

    protected $resourceKey  = 'service';
    protected $resourcesKey = 'services';

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'endpoints' => new Alias('endpoints', Endpoint::class, true),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data {@see \OpenStack\Identity\v3\Api::postServices}
     */
    public function create(array $data): Creatable
    {
        $response = $this->execute($this->api->postServices(), $data);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->executeWithState($this->api->getService());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $response = $this->executeWithState($this->api->patchService());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteService());
    }

    private function nameMatches(string $value): bool
    {
        return $this->name && $this->name == $value;
    }

    private function typeMatches(string $value): bool
    {
        return $this->type && $this->type == $value;
    }

    /**
     * Retrieve the base URL for a service.
     *
     * @param string $name      the name of the service as it appears in the catalog
     * @param string $type      the type of the service as it appears in the catalog
     * @param string $region    the region of the service as it appears in the catalog
     * @param string $interface the interface of the service as it appears in the catalog
     *
     * @return string|false
     */
    public function getUrl(string $name, string $type, string $region, string $interface)
    {
        if (!$this->nameMatches($name) || !$this->typeMatches($type)) {
            return false;
        }

        foreach ($this->endpoints as $endpoint) {
            if ($endpoint->regionMatches($region) && $endpoint->interfaceMatches($interface)) {
                return $endpoint->url;
            }
        }

        return false;
    }
}
