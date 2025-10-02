<?php

namespace Aws\Identity;

/**
 * Denotes the use of Bearer Token credentials.
 *
 * @internal
 */
abstract class BearerTokenIdentity implements IdentityInterface
{
    /**
     * Returns a UNIX timestamp, if available, representing the expiration
     * time of the Bearer Token object. Returns null if no expiration is provided.
     *
     * @return int|null
     */
    abstract public function getExpiration();
}
