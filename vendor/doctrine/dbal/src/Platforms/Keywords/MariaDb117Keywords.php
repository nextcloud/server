<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Platforms\Keywords;

use Doctrine\Deprecations\Deprecation;

use function array_merge;

/**
 * MariaDB 11.7 reserved keywords list.
 */
class MariaDb117Keywords extends MariaDb102Keywords
{
    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function getName(): string
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5433',
            'MariaDb117Keywords::getName() is deprecated.',
        );

        return 'MariaDb117';
    }

    /**
     * {@inheritDoc}
     *
     * @link https://mariadb.com/docs/server/reference/sql-structure/sql-language-structure/reserved-words
     */
    protected function getKeywords(): array
    {
        $keywords = parent::getKeywords();

        // New Keywords and Reserved Words
        $keywords = array_merge($keywords, ['VECTOR']);

        return $keywords;
    }
}
