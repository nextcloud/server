<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Subscriptions;

use Sabre\DAV\ICollection;
use Sabre\DAV\IProperties;

/**
 * ISubscription.
 *
 * Nodes implementing this interface represent calendar subscriptions.
 *
 * The subscription node doesn't do much, other than returning and updating
 * subscription-related properties.
 *
 * The following properties should be supported:
 *
 * 1. {DAV:}displayname
 * 2. {http://apple.com/ns/ical/}refreshrate
 * 3. {http://calendarserver.org/ns/}subscribed-strip-todos (omit if todos
 *    should not be stripped).
 * 4. {http://calendarserver.org/ns/}subscribed-strip-alarms (omit if alarms
 *    should not be stripped).
 * 5. {http://calendarserver.org/ns/}subscribed-strip-attachments (omit if
 *    attachments should not be stripped).
 * 6. {http://calendarserver.org/ns/}source (Must be a
 *     Sabre\DAV\Property\Href).
 * 7. {http://apple.com/ns/ical/}calendar-color
 * 8. {http://apple.com/ns/ical/}calendar-order
 *
 * It is recommended to support every property.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface ISubscription extends ICollection, IProperties
{
}
