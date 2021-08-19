<?php

declare(strict_types=1);

namespace Sabre\DAV\Exception;

use Sabre\DAV;

/**
 * BadRequest.
 *
 * The BadRequest is thrown when the user submitted an invalid HTTP request
 * BadRequest
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class BadRequest extends DAV\Exception
{
    /**
     * Returns the HTTP statuscode for this exception.
     *
     * @return int
     */
    public function getHTTPCode()
    {
        return 400;
    }
}
