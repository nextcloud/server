<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Exception;

use Sabre\DAV;

/**
 * If a client tried to set a privilege that doesn't exist, this exception will
 * be thrown.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class NotSupportedPrivilege extends DAV\Exception\PreconditionFailed
{
    /**
     * Adds in extra information in the xml response.
     *
     * This method adds the {DAV:}not-supported-privilege element as defined in rfc3744
     */
    public function serialize(DAV\Server $server, \DOMElement $errorNode)
    {
        $doc = $errorNode->ownerDocument;

        $np = $doc->createElementNS('DAV:', 'd:not-supported-privilege');
        $errorNode->appendChild($np);
    }
}
