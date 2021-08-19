<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV\Backend;
use Sabre\DAV\Exception\MethodNotAllowed;

/**
 * The SchedulingObject represents a scheduling object in the Inbox collection.
 *
 * @author Brett (https://github.com/bretten)
 * @license http://sabre.io/license/ Modified BSD License
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 */
class SchedulingObject extends \Sabre\CalDAV\CalendarObject implements ISchedulingObject
{
    /**
     * Constructor.
     *
     * The following properties may be passed within $objectData:
     *
     *   * uri - A unique uri. Only the 'basename' must be passed.
     *   * principaluri - the principal that owns the object.
     *   * calendardata (optional) - The iCalendar data
     *   * etag - (optional) The etag for this object, MUST be encloded with
     *            double-quotes.
     *   * size - (optional) The size of the data in bytes.
     *   * lastmodified - (optional) format as a unix timestamp.
     *   * acl - (optional) Use this to override the default ACL for the node.
     */
    public function __construct(Backend\SchedulingSupport $caldavBackend, array $objectData)
    {
        parent::__construct($caldavBackend, [], $objectData);

        if (!isset($objectData['uri'])) {
            throw new \InvalidArgumentException('The objectData argument must contain an \'uri\' property');
        }
    }

    /**
     * Returns the ICalendar-formatted object.
     *
     * @return string
     */
    public function get()
    {
        // Pre-populating the 'calendardata' is optional, if we don't have it
        // already we fetch it from the backend.
        if (!isset($this->objectData['calendardata'])) {
            $this->objectData = $this->caldavBackend->getSchedulingObject($this->objectData['principaluri'], $this->objectData['uri']);
        }

        return $this->objectData['calendardata'];
    }

    /**
     * Updates the ICalendar-formatted object.
     *
     * @param string|resource $calendarData
     *
     * @return string
     */
    public function put($calendarData)
    {
        throw new MethodNotAllowed('Updating scheduling objects is not supported');
    }

    /**
     * Deletes the scheduling message.
     */
    public function delete()
    {
        $this->caldavBackend->deleteSchedulingObject($this->objectData['principaluri'], $this->objectData['uri']);
    }

    /**
     * Returns the owner principal.
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner()
    {
        return $this->objectData['principaluri'];
    }

    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    public function getACL()
    {
        // An alternative acl may be specified in the object data.
        //

        if (isset($this->objectData['acl'])) {
            return $this->objectData['acl'];
        }

        // The default ACL
        return [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->objectData['principaluri'].'/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->objectData['principaluri'].'/calendar-proxy-read',
                'protected' => true,
            ],
        ];
    }
}
