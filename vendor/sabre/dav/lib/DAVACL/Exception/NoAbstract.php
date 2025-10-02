<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Exception;

use Sabre\DAV;

/**
 * This exception is thrown when a user tries to set a privilege that's marked
 * as abstract.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class NoAbstract extends DAV\Exception\PreconditionFailed
{
    /**
     * Adds in extra information in the xml response.
     *
     * This method adds the {DAV:}no-abstract element as defined in rfc3744
     */
    public function serialize(DAV\Server $server, \DOMElement $errorNode)
    {
        $doc = $errorNode->ownerDocument;

        $np = $doc->createElementNS('DAV:', 'd:no-abstract');
        $errorNode->appendChild($np);
    }
}
