<?php

declare(strict_types=1);

namespace Sabre\DAV\Exception;

use Sabre\DAV;

/**
 * Conflict.
 *
 * A 409 Conflict is thrown when a user tried to make a directory over an existing
 * file or in a parent directory that doesn't exist.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Conflict extends DAV\Exception
{
    /**
     * Returns the HTTP statuscode for this exception.
     *
     * @return int
     */
    public function getHTTPCode()
    {
        return 409;
    }
}
