<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use Sabre\DAV;

/**
 * CalendarObject interface.
 *
 * Extend the ICalendarObject interface to allow your custom nodes to be picked up as
 * CalendarObjects.
 *
 * Calendar objects are resources such as Events, Todo's or Journals.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface ICalendarObject extends DAV\IFile
{
}
