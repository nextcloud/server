<?php

namespace Aws\Identity;

/**
 * An Identity object is used in identifying credential types and determining how
 * the SDK authenticates with a service API for requests that require a signature.
 *
 * @internal
 */
interface IdentityInterface
{
    /**
     * Returns a UNIX timestamp, if available, representing
     * the expiration time of the identity object.  Returns null
     * if no expiration is provided.
     *
     * @return int|null
     */
    public function getExpiration();
}
