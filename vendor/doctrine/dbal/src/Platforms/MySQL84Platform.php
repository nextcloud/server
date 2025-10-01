<?php

namespace Doctrine\DBAL\Platforms;

use Doctrine\Deprecations\Deprecation;

/**
 * Provides the behavior, features and SQL dialect of the MySQL 8.4 database platform.
 */
class MySQL84Platform extends MySQL80Platform
{
    /**
     * {@inheritDoc}
     *
     * @deprecated Implement {@see createReservedKeywordsList()} instead.
     */
    protected function getReservedKeywordsClass()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4510',
            'MySQL84Platform::getReservedKeywordsClass() is deprecated,'
                . ' use MySQL84Platform::createReservedKeywordsList() instead.',
        );

        return Keywords\MySQL84Keywords::class;
    }
}
