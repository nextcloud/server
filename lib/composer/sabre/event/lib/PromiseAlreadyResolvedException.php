<?php

declare(strict_types=1);

namespace Sabre\Event;

/**
 * This exception is thrown when the user tried to reject or fulfill a promise,
 * after either of these actions were already performed.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PromiseAlreadyResolvedException extends \LogicException
{
}
