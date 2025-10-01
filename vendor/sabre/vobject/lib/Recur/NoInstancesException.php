<?php

namespace Sabre\VObject\Recur;

use Exception;

/**
 * This exception gets thrown when a recurrence iterator produces 0 instances.
 *
 * This may happen when every occurrence in a rrule is also in EXDATE.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class NoInstancesException extends Exception
{
}
