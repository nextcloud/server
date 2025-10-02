<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3;

abstract class Enum
{
    public const INTERFACE_INTERNAL = 'internal';
    public const INTERFACE_PUBLIC   = 'public';
    public const INTERFACE_ADMIN    = 'admin';
}
