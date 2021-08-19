<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Notifications;

use Sabre\DAV;

/**
 * This node represents a list of notifications.
 *
 * It provides no additional functionality, but you must implement this
 * interface to allow the Notifications plugin to mark the collection
 * as a notifications collection.
 *
 * This collection should only return Sabre\CalDAV\Notifications\INode nodes as
 * its children.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface ICollection extends DAV\ICollection
{
}
