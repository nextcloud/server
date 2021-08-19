<?php

declare(strict_types=1);

namespace OpenStack\Common\Auth;

interface Catalog
{
    /**
     * Attempts to retrieve the base URL for a service from the catalog according to the arguments provided.
     *
     * @param string $name    The name of the service as it appears in the catalog
     * @param string $type    The type of the service as it appears in the catalog
     * @param string $region  The region of the service as it appears in the catalog
     * @param string $urlType The URL type of the service as it appears in the catalog
     *
     * @throws \RuntimeException If no endpoint is matched
     *
     * @returns string
     */
    public function getServiceUrl(string $name, string $type, string $region, string $urlType): string;
}
