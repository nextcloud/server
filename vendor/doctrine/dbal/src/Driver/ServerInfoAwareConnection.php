<?php

namespace Doctrine\DBAL\Driver;

/**
 * Contract for a connection that is able to provide information about the server it is connected to.
 *
 * @deprecated The methods defined in this interface will be made part of the {@see Driver} interface
 * in the next major release.
 */
interface ServerInfoAwareConnection extends Connection
{
    /**
     * Returns information about the version of the database server connected to.
     *
     * @return string
     *
     * @throws Exception
     */
    public function getServerVersion();
}
