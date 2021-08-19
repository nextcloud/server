<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV\Xml\Notification\NotificationInterface;

/**
 * Adds caldav notification support to a backend.
 *
 * Note: This feature is experimental, and may change in between different
 * SabreDAV versions.
 *
 * Notifications are defined at:
 * http://svn.calendarserver.org/repository/calendarserver/CalendarServer/trunk/doc/Extensions/caldav-notifications.txt
 *
 * These notifications are basically a list of server-generated notifications
 * displayed to the user. Users can dismiss notifications by deleting them.
 *
 * The primary usecase is to allow for calendar-sharing.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface NotificationSupport extends BackendInterface
{
    /**
     * Returns a list of notifications for a given principal url.
     *
     * @param string $principalUri
     *
     * @return NotificationInterface[]
     */
    public function getNotificationsForPrincipal($principalUri);

    /**
     * This deletes a specific notifcation.
     *
     * This may be called by a client once it deems a notification handled.
     *
     * @param string $principalUri
     */
    public function deleteNotification($principalUri, NotificationInterface $notification);

    /**
     * This method is called when a user replied to a request to share.
     *
     * If the user chose to accept the share, this method should return the
     * newly created calendar url.
     *
     * @param string $href        The sharee who is replying (often a mailto: address)
     * @param int    $status      One of the SharingPlugin::STATUS_* constants
     * @param string $calendarUri The url to the calendar thats being shared
     * @param string $inReplyTo   The unique id this message is a response to
     * @param string $summary     A description of the reply
     *
     * @return string|null
     */
    public function shareReply($href, $status, $calendarUri, $inReplyTo, $summary = null);
}
