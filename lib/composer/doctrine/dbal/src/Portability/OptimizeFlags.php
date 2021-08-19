<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Portability;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServer2012Platform;

final class OptimizeFlags
{
    /**
     * Platform-specific portability flags that need to be excluded from the user-provided mode
     * since the platform already operates in this mode to avoid unnecessary conversion overhead.
     *
     * @var array<string,int>
     */
    private static $platforms = [
        DB2Platform::class           => 0,
        OraclePlatform::class        => Connection::PORTABILITY_EMPTY_TO_NULL,
        PostgreSQL94Platform::class  => 0,
        SqlitePlatform::class        => 0,
        SQLServer2012Platform::class => 0,
    ];

    public function __invoke(AbstractPlatform $platform, int $flags): int
    {
        foreach (self::$platforms as $class => $mask) {
            if ($platform instanceof $class) {
                $flags &= ~$mask;

                break;
            }
        }

        return $flags;
    }
}
