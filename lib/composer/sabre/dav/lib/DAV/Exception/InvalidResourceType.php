<?php

declare(strict_types=1);

namespace Sabre\DAV\Exception;

/**
 * InvalidResourceType.
 *
 * This exception is thrown when the user tried to create a new collection, with
 * a special resourcetype value that was not recognized by the server.
 *
 * See RFC5689 section 3.3
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class InvalidResourceType extends Forbidden
{
    /**
     * This method allows the exception to include additional information into the WebDAV error response.
     */
    public function serialize(\Sabre\DAV\Server $server, \DOMElement $errorNode)
    {
        $error = $errorNode->ownerDocument->createElementNS('DAV:', 'd:valid-resourcetype');
        $errorNode->appendChild($error);
    }
}
