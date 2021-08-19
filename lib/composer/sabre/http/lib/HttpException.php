<?php

declare(strict_types=1);

namespace Sabre\HTTP;

/**
 * An exception representing a HTTP error.
 *
 * This can be used as a generic exception in your application, if you'd like
 * to map HTTP errors to exceptions.
 *
 * If you'd like to use this, create a new exception class, extending Exception
 * and implementing this interface.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface HttpException
{
    /**
     * The http status code for the error.
     *
     * This may either be just the number, or a number and a human-readable
     * message, separated by a space.
     *
     * @return string|null
     */
    public function getHttpStatus();
}
