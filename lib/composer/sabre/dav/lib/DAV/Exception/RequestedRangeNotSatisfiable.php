<?php

declare(strict_types=1);

namespace Sabre\DAV\Exception;

use Sabre\DAV;

/**
 * RequestedRangeNotSatisfiable.
 *
 * This exception is normally thrown when the user
 * request a range that is out of the entity bounds.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class RequestedRangeNotSatisfiable extends DAV\Exception
{
    /**
     * returns the http statuscode for this exception.
     *
     * @return int
     */
    public function getHTTPCode()
    {
        return 416;
    }
}
