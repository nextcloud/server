<?php


namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\Utilities\EnumType;

/**
 * Describes how a file is checked out of a document library.
 */
class CheckOutType extends EnumType
{
    /**
     * The file is checked out for editing on the server.
     */
    const Online = 0;

    /**
     * The file is checked out for editing on the local computer.
     */
    const Offline = 1;

    /**
     * The file is not checked out.
     */
    const None = 2;
}