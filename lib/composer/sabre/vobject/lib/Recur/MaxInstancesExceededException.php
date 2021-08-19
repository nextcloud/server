<?php

namespace Sabre\VObject\Recur;

use Exception;

/**
 * This exception will get thrown when a recurrence rule generated more than
 * the maximum number of instances.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class MaxInstancesExceededException extends Exception
{
}
