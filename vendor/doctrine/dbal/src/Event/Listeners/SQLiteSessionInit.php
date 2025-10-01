<?php

namespace Doctrine\DBAL\Event\Listeners;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Exception;

/** @deprecated Use {@see \Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys} instead. */
class SQLiteSessionInit implements EventSubscriber
{
    /**
     * @return void
     *
     * @throws Exception
     */
    public function postConnect(ConnectionEventArgs $args)
    {
        $args->getConnection()->executeStatement('PRAGMA foreign_keys=ON');
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return [Events::postConnect];
    }
}
