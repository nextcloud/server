<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Platforms;

use Doctrine\Deprecations\Deprecation;

/**
 * Provides the behavior, features and SQL dialect of the MariaDB 11.7 database platform.
 */
class MariaDb110700Platform extends MariaDb1010Platform
{
/** @deprecated Implement {@see createReservedKeywordsList()} instead. */
    protected function getReservedKeywordsClass(): string
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4510',
            'MariaDb110700Platform::getReservedKeywordsClass() is deprecated,'
                . ' use MariaDb110700Platform::createReservedKeywordsList() instead.',
        );

        return Keywords\MariaDb117Keywords::class;
    }
}
