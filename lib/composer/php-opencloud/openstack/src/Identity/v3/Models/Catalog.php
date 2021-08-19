<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\OperatorResource;

/**
 * @property \OpenStack\Identity\v3\Api $api
 */
class Catalog extends OperatorResource implements \OpenStack\Common\Auth\Catalog
{
    /** @var []Service */
    public $services;

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'services' => new Alias('services', Service::class, true),
        ];
    }

    public function populateFromArray(array $data): self
    {
        foreach ($data as $service) {
            $this->services[] = $this->model(Service::class, $service);
        }

        return $this;
    }

    /**
     * Retrieve a base URL for a service, according to its catalog name, type, region.
     *
     * @param string $name    the name of the service as it appears in the catalog
     * @param string $type    the type of the service as it appears in the catalog
     * @param string $region  the region of the service as it appears in the catalog
     * @param string $urlType unused
     *
     * @return false|string FALSE if no URL found
     */
    public function getServiceUrl(string $name, string $type, string $region, string $urlType): string
    {
        if (empty($this->services)) {
            throw new \RuntimeException('No services are defined');
        }

        foreach ($this->services as $service) {
            if (false !== ($url = $service->getUrl($name, $type, $region, $urlType))) {
                return $url;
            }
        }

        throw new \RuntimeException(sprintf("Endpoint URL could not be found in the catalog for this service.\nName: %s\nType: %s\nRegion: %s\nURL type: %s", $name, $type, $region, $urlType));
    }
}
