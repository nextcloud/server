<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Notifications;

use Sabre\CalDAV\Xml\Notification\NotificationInterface;

/**
 * This node represents a single notification.
 *
 * The signature is mostly identical to that of Sabre\DAV\IFile, but the get() method
 * MUST return an xml document that matches the requirements of the
 * 'caldav-notifications.txt' spec.
 *
 * For a complete example, check out the Notification class, which contains
 * some helper functions.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface INode
{
    /**
     * This method must return an xml element, using the
     * Sabre\CalDAV\Xml\Notification\NotificationInterface classes.
     *
     * @return NotificationInterface
     */
    public function getNotificationType();

    /**
     * Returns the etag for the notification.
     *
     * The etag must be surrounded by literal double-quotes.
     *
     * @return string
     */
    public function getETag();
}
