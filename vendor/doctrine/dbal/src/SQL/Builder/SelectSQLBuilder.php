<?php

namespace Doctrine\DBAL\SQL\Builder;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\SelectQuery;

interface SelectSQLBuilder
{
    /** @throws Exception */
    public function buildSQL(SelectQuery $query): string;
}
