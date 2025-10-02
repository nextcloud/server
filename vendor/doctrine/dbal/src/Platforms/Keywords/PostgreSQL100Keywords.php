<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Platforms\Keywords;

use Doctrine\Deprecations\Deprecation;

/**
 * PostgreSQL 10.0 reserved keywords list.
 *
 * @deprecated Use {@link PostgreSQLKeywords} instead.
 */
class PostgreSQL100Keywords extends PostgreSQL94Keywords
{
    /** @deprecated */
    public function getName(): string
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5433',
            'PostgreSQL100Keywords::getName() is deprecated.',
        );

        return 'PostgreSQL100';
    }
}
