<?php

declare(strict_types=1);

namespace OpenStack\Identity\v3;

abstract class Enum
{
    const INTERFACE_INTERNAL = 'internal';
    const INTERFACE_PUBLIC   = 'public';
    const INTERFACE_ADMIN    = 'admin';
}
