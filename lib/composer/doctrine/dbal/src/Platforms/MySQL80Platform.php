<?php

namespace Doctrine\DBAL\Platforms;

/**
 * Provides the behavior, features and SQL dialect of the MySQL 8.0 (8.0 GA) database platform.
 */
class MySQL80Platform extends MySQL57Platform
{
    /**
     * {@inheritdoc}
     */
    protected function getReservedKeywordsClass()
    {
        return Keywords\MySQL80Keywords::class;
    }
}
