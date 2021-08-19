<?php

declare(strict_types=1);

namespace OpenStack\Identity\v2\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Transport\Utils;
use Psr\Http\Message\ResponseInterface;

/**
 * Represents an Identity v2 service catalog.
 */
class Catalog extends OperatorResource implements \OpenStack\Common\Auth\Catalog
{
    const DEFAULT_URL_TYPE = 'publicURL';

    /**
     * The catalog entries.
     *
     * @var []Entry
     */
    public $entries = [];

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'entries' => new Alias('entries', Entry::class, true),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function populateFromResponse(ResponseInterface $response): self
    {
        $entries = Utils::jsonDecode($response)['access']['serviceCatalog'];

        foreach ($entries as $entry) {
            $this->entries[] = $this->model(Entry::class, $entry);
        }

        return $this;
    }

    public function getServiceUrl(
        string $serviceName,
        string $serviceType,
        string $region,
        string $urlType = self::DEFAULT_URL_TYPE
    ): string {
        foreach ($this->entries as $entry) {
            if ($entry->matches($serviceName, $serviceType) && ($url = $entry->getEndpointUrl($region, $urlType))) {
                return $url;
            }
        }

        throw new \RuntimeException(sprintf("Endpoint URL could not be found in the catalog for this service.\nName: %s\nType: %s\nRegion: %s\nURL type: %s", $serviceName, $serviceType, $region, $urlType));
    }
}
