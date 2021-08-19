<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Platforms;

final class TrimMode
{
    public const UNSPECIFIED = 0;

    public const LEADING = 1;

    public const TRAILING = 2;

    public const BOTH = 3;

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
