<?php

declare(strict_types=1);

namespace OpenStack\Common\Auth;

interface IdentityService
{
    /**
     * Authenticates and retrieves back a token and catalog.
     *
     * @return array The FIRST key is {@see Token} instance, the SECOND key is a {@see Catalog} instance
     */
    public function authenticate(array $options): array;
}
