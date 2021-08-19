<?php

declare(strict_types=1);

namespace OpenStack\Identity\v2\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\OperatorResource;

/**
 * Represents an Identity v2 Catalog Entry.
 */
class Entry extends OperatorResource
{
    /** @var string */
    public $name;

    /** @var string */
    public $type;

    /** @var []Endpoint */
    public $endpoints = [];

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
     * Indicates whether this catalog entry matches a certain name and type.
     *
     * @return bool TRUE if it's a match, FALSE if not
     */
    public function matches(string $name, string $type): bool
    {
        return $this->name == $name && $this->type == $type;
    }

    /**
     * Retrieves the catalog entry's URL according to a specific region and URL type.
     */
    public function getEndpointUrl(string $region, string $urlType): string
    {
        foreach ($this->endpoints as $endpoint) {
            if ($endpoint->supportsRegion($region) && $endpoint->supportsUrlType($urlType)) {
                return $endpoint->getUrl($urlType);
            }
        }

        return '';
    }
}
