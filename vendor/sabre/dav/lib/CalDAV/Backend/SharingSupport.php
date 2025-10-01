<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Backend;

/**
 * Adds support for sharing features to a CalDAV server.
 *
 * CalDAV backends that implement this interface, must make the following
 * modifications to getCalendarsForUser:
 *
 * 1. Return shared calendars for users.
 * 2. For every calendar, return calendar-resource-uri. This strings is a URI or
 *    relative URI reference that must be unique for every calendar, but
 *    identical for every instance of the same shared calendar.
 * 3. For every calendar, you must return a share-access element. This element
 *    should contain one of the Sabre\DAV\Sharing\Plugin:ACCESS_* constants and
 *    indicates the access level the user has.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface SharingSupport extends BackendInterface
{
    /**
     * Updates the list of shares.
     *
     * @param mixed                           $calendarId
     * @param \Sabre\DAV\Xml\Element\Sharee[] $sharees
     */
    public function updateInvites($calendarId, array $sharees);

    /**
     * Returns the list of people whom this calendar is shared with.
     *
     * Every item in the returned list must be a Sharee object with at
     * least the following properties set:
     *   $href
     *   $shareAccess
     *   $inviteStatus
     *
     * and optionally:
     *   $properties
     *
     * @param mixed $calendarId
     *
     * @return \Sabre\DAV\Xml\Element\Sharee[]
     */
    public function getInvites($calendarId);

    /**
     * Publishes a calendar.
     *
     * @param mixed $calendarId
     * @param bool  $value
     */
    public function setPublishStatus($calendarId, $value);
}
