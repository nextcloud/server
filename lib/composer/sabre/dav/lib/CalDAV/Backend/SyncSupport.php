<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Backend;

/**
 * WebDAV-sync support for CalDAV backends.
 *
 * In order for backends to advertise support for WebDAV-sync, this interface
 * must be implemented.
 *
 * Implementing this can result in a significant reduction of bandwidth and CPU
 * time.
 *
 * For this to work, you _must_ return a {http://sabredav.org/ns}sync-token
 * property from getCalendarsFromUser.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface SyncSupport extends BackendInterface
{
    /**
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken in the specified calendar.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'modified.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * );
     *
     * The returned syncToken property should reflect the *current* syncToken
     * of the calendar, as reported in the {http://sabredav.org/ns}sync-token
     * property This is * needed here too, to ensure the operation is atomic.
     *
     * If the $syncToken argument is specified as null, this is an initial
     * sync, and all members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The $syncLevel argument is basically the 'depth' of the report. If it's
     * 1, you only have to report changes that happened only directly in
     * immediate descendants. If it's 2, it should also include changes from
     * the nodes below the child collections. (grandchildren)
     *
     * The $limit argument allows a client to specify how many results should
     * be returned at most. If the limit is not specified, it should be treated
     * as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param string $calendarId
     * @param string $syncToken
     * @param int    $syncLevel
     * @param int    $limit
     *
     * @return array
     */
    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null);
}
