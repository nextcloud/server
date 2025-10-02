<?php

declare(strict_types=1);

namespace OpenStack\Identity\v2\Models;

use OpenStack\Common\HydratorStrategyTrait;
use OpenStack\Common\Resource\OperatorResource;

/**
 * Represents an Identity v2 catalog entry endpoint.
 */
class Endpoint extends OperatorResource
{
    use HydratorStrategyTrait;

    /** @var string */
    public $adminUrl;

    /** @var string */
    public $region;

    /** @var string */
    public $internalUrl;

    /** @var string */
    public $publicUrl;

    protected $aliases = [
        'adminURL'    => 'adminUrl',
        'internalURL' => 'internalUrl',
        'publicURL'   => 'publicUrl',
    ];

    /**
     * Indicates whether a given region is supported.
     */
    public function supportsRegion(string $region): bool
    {
        return $this->region == $region;
    }

    /**
     * Indicates whether a given URL type is supported.
     */
    public function supportsUrlType(string $urlType): bool
    {
        $supported = false;

        switch (strtolower($urlType)) {
            case 'internalurl':
            case 'publicurl':
            case 'adminurl':
                $supported = true;
                break;
        }

        return $supported;
    }

    /**
     * Retrieves a URL for the endpoint based on a specific type.
     *
     * @param string $urlType Either "internalURL", "publicURL" or "adminURL" (case insensitive)
     *
     * @return bool|string
     */
    public function getUrl(string $urlType): string
    {
        $url = false;

        switch (strtolower($urlType)) {
            case 'internalurl':
                $url = $this->internalUrl;
                break;
            case 'publicurl':
                $url = $this->publicUrl;
                break;
            case 'adminurl':
                $url = $this->adminUrl;
                break;
        }

        return $url;
    }
}
