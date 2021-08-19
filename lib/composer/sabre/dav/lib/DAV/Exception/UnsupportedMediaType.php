<?php

declare(strict_types=1);

namespace Sabre\DAV\Exception;

use Sabre\DAV;

/**
 * UnSupportedMediaType.
 *
 * The 415 Unsupported Media Type status code is generally sent back when the client
 * tried to call an HTTP method, with a body the server didn't understand
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class UnsupportedMediaType extends DAV\Exception
{
    /**
     * returns the http statuscode for this exception.
     *
     * @return int
     */
    public function getHTTPCode()
    {
        return 415;
    }
}
