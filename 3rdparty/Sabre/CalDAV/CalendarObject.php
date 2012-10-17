<?php

/**
 * The CalendarObject represents a single VEVENT or VTODO within a Calendar.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_CalendarObject extends Sabre_DAV_File implements Sabre_CalDAV_ICalendarObject, Sabre_DAVACL_IACL {

    /**
     * Sabre_CalDAV_Backend_BackendInterface
     *
     * @var array
     */
    protected $caldavBackend;

    /**
     * Array with information about this CalendarObject
     *
     * @var array
     */
    protected $objectData;

    /**
     * Array with information about the containing calendar
     *
     * @var array
     */
    protected $calendarInfo;

    /**
     * Constructor
     *
     * @param Sabre_CalDAV_Backend_BackendInterface $caldavBackend
     * @param array $calendarInfo
     * @param array $objectData
     */
    public function __construct(Sabre_CalDAV_Backend_BackendInterface $caldavBackend,array $calendarInfo,array $objectData) {

        $this->caldavBackend = $caldavBackend;

        if (!isset($objectData['calendarid'])) {
            throw new InvalidArgumentException('The objectData argument must contain a \'calendarid\' property');
        }
        if (!isset($objectData['uri'])) {
            throw new InvalidArgumentException('The objectData argument must contain an \'uri\' property');
        }

        $this->calendarInfo = $calendarInfo;
        $this->objectData = $objectData;

    }

    /**
     * Returns the uri for this object
     *
     * @return string
     */
    public function getName() {

        return $this->objectData['uri'];

    }

    /**
     * Returns the ICalendar-formatted object
     *
     * @return string
     */
    public function get() {

        // Pre-populating the 'calendardata' is optional, if we don't have it
        // already we fetch it from the backend.
        if (!isset($this->objectData['calendardata'])) {
            $this->objectData = $this->caldavBackend->getCalendarObject($this->objectData['calendarid'], $this->objectData['uri']);
        }
        return $this->objectData['calendardata'];

    }

    /**
     * Updates the ICalendar-formatted object
     *
     * @param string|resource $calendarData
     * @return string
     */
    public function put($calendarData) {

        if (is_resource($calendarData)) {
            $calendarData = stream_get_contents($calendarData);
        }
        $etag = $this->caldavBackend->updateCalendarObject($this->calendarInfo['id'],$this->objectData['uri'],$calendarData);
        $this->objectData['calendardata'] = $calendarData;
        $this->objectData['etag'] = $etag;

        return $etag;

    }

    /**
     * Deletes the calendar object
     *
     * @return void
     */
    public function delete() {

        $this->caldavBackend->deleteCalendarObject($this->calendarInfo['id'],$this->objectData['uri']);

    }

    /**
     * Returns the mime content-type
     *
     * @return string
     */
    public function getContentType() {

        return 'text/calendar; charset=utf-8';

    }

    /**
     * Returns an ETag for this object.
     *
     * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
     *
     * @return string
     */
    public function getETag() {

        if (isset($this->objectData['etag'])) {
            return $this->objectData['etag'];
        } else {
            return '"' . md5($this->get()). '"';
        }

    }

    /**
     * Returns the last modification date as a unix timestamp
     *
     * @return int
     */
    public function getLastModified() {

        return $this->objectData['lastmodified'];

    }

    /**
     * Returns the size of this object in bytes
     *
     * @return int
     */
    public function getSize() {

        if (array_key_exists('size',$this->objectData)) {
            return $this->objectData['size'];
        } else {
            return strlen($this->get());
        }

    }

    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner() {

        return $this->calendarInfo['principaluri'];

    }

    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getGroup() {

        return null;

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
    public function getACL() {

        // An alternative acl may be specified in the object data.
        if (isset($this->objectData['acl'])) {
            return $this->objectData['acl'];
        }

        // The default ACL
        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->calendarInfo['principaluri'],
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $this->calendarInfo['principaluri'],
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->calendarInfo['principaluri'] . '/calendar-proxy-read',
                'protected' => true,
            ),

        );

    }

    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's.
     *
     * @param array $acl
     * @return void
     */
    public function setACL(array $acl) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Changing ACL is not yet supported');

    }

    /**
     * Returns the list of supported privileges for this node.
     *
     * The returned data structure is a list of nested privileges.
     * See Sabre_DAVACL_Plugin::getDefaultSupportedPrivilegeSet for a simple
     * standard structure.
     *
     * If null is returned from this method, the default privilege set is used,
     * which is fine for most common usecases.
     *
     * @return array|null
     */
    public function getSupportedPrivilegeSet() {

        return null;

    }

}

