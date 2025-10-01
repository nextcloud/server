<?php

namespace Sabre\VObject\ITip;

/**
 * SameOrganizerForAllComponentsException.
 *
 * This exception is emitted when an event is encountered with more than one
 * component (e.g.: exceptions), but the organizer is not identical in every
 * component.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SameOrganizerForAllComponentsException extends ITipException
{
}
