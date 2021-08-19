<?php

declare(strict_types=1);

namespace OpenStack\Common\Auth;

interface Token
{
    public function getId(): string;

    /**
     * Indicates whether the token has expired or not.
     *
     * @return bool TRUE if the token has expired, FALSE if it is still valid
     */
    public function hasExpired(): bool;
}
