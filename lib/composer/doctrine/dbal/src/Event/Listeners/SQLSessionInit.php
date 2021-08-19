<?php

namespace Doctrine\DBAL\Event\Listeners;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Exception;

/**
 * Session init listener for executing a single SQL statement right after a connection is opened.
 */
class SQLSessionInit implements EventSubscriber
{
    /** @var string */
    protected $sql;

    /**
     * @param string $sql
     */
    public function __construct($sql)
    {
        $this->sql = $sql;
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function postConnect(ConnectionEventArgs $args)
    {
        $args->getConnection()->executeStatement($this->sql);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [Events::postConnect];
    }
}
