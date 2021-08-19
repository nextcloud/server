<?php

namespace Doctrine\DBAL\Driver;

/**
 * Contract for a connection that is able to provide information about the server it is connected to.
 */
interface ServerInfoAwareConnection extends Connection
{
    /**
     * Returns the version number of the database server connected to.
     *
     * @return string
     *
     * @throws Exception
     */
    public function getServerVersion();
}
