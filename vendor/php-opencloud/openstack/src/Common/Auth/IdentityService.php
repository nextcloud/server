<?php

declare(strict_types=1);

namespace OpenStack\Common\Auth;

interface IdentityService
{
    /**
     * Authenticates and retrieves back a token and catalog.
     *
     * @return array{0: \OpenStack\Common\Auth\Token, 1: string} The FIRST key is {@see Token} instance, the SECOND key is a URL of the service
     */
    public function authenticate(array $options): array;
}
