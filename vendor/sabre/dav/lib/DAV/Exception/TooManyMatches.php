<?php

declare(strict_types=1);

namespace Sabre\DAV\Exception;

use Sabre\DAV;

/**
 * TooManyMatches.
 *
 * This exception is emitted for the {DAV:}number-of-matches-within-limits
 * post-condition, as defined in rfc6578, section 3.2.
 *
 * http://tools.ietf.org/html/rfc6578#section-3.2
 *
 * This is emitted in cases where the response to a {DAV:}sync-collection would
 * generate more results than the implementation is willing to send back.
 *
 * @author Evert Pot (http://evertpot.com/)
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class TooManyMatches extends Forbidden
{
    /**
     * This method allows the exception to include additional information into the WebDAV error response.
     */
    public function serialize(DAV\Server $server, \DOMElement $errorNode)
    {
        $error = $errorNode->ownerDocument->createElementNS('DAV:', 'd:number-of-matches-within-limits');
        $errorNode->appendChild($error);
    }
}
