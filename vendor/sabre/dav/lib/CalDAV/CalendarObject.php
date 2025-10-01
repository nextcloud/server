<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

/**
 * The CalendarObject represents a single VEVENT or VTODO within a Calendar.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class CalendarObject extends \Sabre\DAV\File implements ICalendarObject, \Sabre\DAVACL\IACL
{
    use \Sabre\DAVACL\ACLTrait;

    /**
     * Sabre\CalDAV\Backend\BackendInterface.
     *
     * @var Backend\AbstractBackend
     */
    protected $caldavBackend;

    /**
     * Array with information about this CalendarObject.
     *
     * @var array
     */
    protected $objectData;

    /**
     * Array with information about the containing calendar.
     *
     * @var array
     */
    protected $calendarInfo;

    /**
     * Constructor.
     *
     * The following properties may be passed within $objectData:
     *
     *   * calendarid - This must refer to a calendarid from a caldavBackend
     *   * uri - A unique uri. Only the 'basename' must be passed.
     *   * calendardata (optional) - The iCalendar data
     *   * etag - (optional) The etag for this object, MUST be encloded with
     *            double-quotes.
     *   * size - (optional) The size of the data in bytes.
     *   * lastmodified - (optional) format as a unix timestamp.
     *   * acl - (optional) Use this to override the default ACL for the node.
     */
    public function __construct(Backend\BackendInterface $caldavBackend, array $calendarInfo, array $objectData)
    {
        $this->caldavBackend = $caldavBackend;

        if (!isset($objectData['uri'])) {
            throw new \InvalidArgumentException('The objectData argument must contain an \'uri\' property');
        }

        $this->calendarInfo = $calendarInfo;
        $this->objectData = $objectData;
    }

    /**
     * Returns the uri for this object.
     *
     * @return string
     */
    public function getName()
    {
        return $this->objectData['uri'];
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
            $this->objectData = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'], $this->objectData['uri']);
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
        if (is_resource($calendarData)) {
            $calendarData = stream_get_contents($calendarData);
        }
        $etag = $this->caldavBackend->updateCalendarObject($this->calendarInfo['id'], $this->objectData['uri'], $calendarData);
        $this->objectData['calendardata'] = $calendarData;
        $this->objectData['etag'] = $etag;

        return $etag;
    }

    /**
     * Deletes the calendar object.
     */
    public function delete()
    {
        $this->caldavBackend->deleteCalendarObject($this->calendarInfo['id'], $this->objectData['uri']);
    }

    /**
     * Returns the mime content-type.
     *
     * @return string
     */
    public function getContentType()
    {
        $mime = 'text/calendar; charset=utf-8';
        if (isset($this->objectData['component']) && $this->objectData['component']) {
            $mime .= '; component='.$this->objectData['component'];
        }

        return $mime;
    }

    /**
     * Returns an ETag for this object.
     *
     * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
     *
     * @return string
     */
    public function getETag()
    {
        if (isset($this->objectData['etag'])) {
            return $this->objectData['etag'];
        } else {
            return '"'.md5($this->get()).'"';
        }
    }

    /**
     * Returns the last modification date as a unix timestamp.
     *
     * @return int
     */
    public function getLastModified()
    {
        return $this->objectData['lastmodified'];
    }

    /**
     * Returns the size of this object in bytes.
     *
     * @return int
     */
    public function getSize()
    {
        if (array_key_exists('size', $this->objectData)) {
            return $this->objectData['size'];
        } else {
            return strlen($this->get());
        }
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
        return $this->calendarInfo['principaluri'];
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
        if (isset($this->objectData['acl'])) {
            return $this->objectData['acl'];
        }

        // The default ACL
        return [
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->calendarInfo['principaluri'],
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->calendarInfo['principaluri'].'/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->calendarInfo['principaluri'].'/calendar-proxy-read',
                'protected' => true,
            ],
        ];
    }
}
