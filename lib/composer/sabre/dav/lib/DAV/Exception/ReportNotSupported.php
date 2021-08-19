<?php

declare(strict_types=1);

namespace Sabre\DAV\Exception;

use Sabre\DAV;

/**
 * ReportNotSupported.
 *
 * This exception is thrown when the client requested an unknown report through the REPORT method
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ReportNotSupported extends UnsupportedMediaType
{
    /**
     * This method allows the exception to include additional information into the WebDAV error response.
     */
    public function serialize(DAV\Server $server, \DOMElement $errorNode)
    {
        $error = $errorNode->ownerDocument->createElementNS('DAV:', 'd:supported-report');
        $errorNode->appendChild($error);
    }
}
