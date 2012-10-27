<?php

/**
 * This object represents a CalDAV calendar.
 *
 * A calendar can contain multiple TODO and or Events. These are represented
 * as Sabre_CalDAV_CalendarObject objects.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Calendar implements Sabre_CalDAV_ICalendar, Sabre_DAV_IProperties, Sabre_DAVACL_IACL {

    /**
     * This is an array with calendar information
     *
     * @var array
     */
    protected $calendarInfo;

    /**
     * CalDAV backend
     *
     * @var Sabre_CalDAV_Backend_BackendInterface
     */
    protected $caldavBackend;

    /**
     * Principal backend
     *
     * @var Sabre_DAVACL_IPrincipalBackend
     */
    protected $principalBackend;

    /**
     * Constructor
     *
     * @param Sabre_DAVACL_IPrincipalBackend $principalBackend
     * @param Sabre_CalDAV_Backend_BackendInterface $caldavBackend
     * @param array $calendarInfo
     */
    public function __construct(Sabre_DAVACL_IPrincipalBackend $principalBackend, Sabre_CalDAV_Backend_BackendInterface $caldavBackend, $calendarInfo) {

        $this->caldavBackend = $caldavBackend;
        $this->principalBackend = $principalBackend;
        $this->calendarInfo = $calendarInfo;

    }

    /**
     * Returns the name of the calendar
     *
     * @return string
     */
    public function getName() {

        return $this->calendarInfo['uri'];

    }

    /**
     * Updates properties such as the display name and description
     *
     * @param array $mutations
     * @return array
     */
    public function updateProperties($mutations) {

        return $this->caldavBackend->updateCalendar($this->calendarInfo['id'],$mutations);

    }

    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties) {

        $response = array();

        foreach($requestedProperties as $prop) switch($prop) {

            case '{urn:ietf:params:xml:ns:caldav}supported-calendar-data' :
                $response[$prop] = new Sabre_CalDAV_Property_SupportedCalendarData();
                break;
            case '{urn:ietf:params:xml:ns:caldav}supported-collation-set' :
                $response[$prop] =  new Sabre_CalDAV_Property_SupportedCollationSet();
                break;
            default :
                if (isset($this->calendarInfo[$prop])) $response[$prop] = $this->calendarInfo[$prop];
                break;

        }
        return $response;

    }

    /**
     * Returns a calendar object
     *
     * The contained calendar objects are for example Events or Todo's.
     *
     * @param string $name
     * @return Sabre_CalDAV_ICalendarObject
     */
    public function getChild($name) {

        $obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'],$name);
        if (!$obj) throw new Sabre_DAV_Exception_NotFound('Calendar object not found');

        $obj['acl'] = $this->getACL();
        // Removing the irrelivant
        foreach($obj['acl'] as $key=>$acl) {
            if ($acl['privilege'] === '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}read-free-busy') {
                unset($obj['acl'][$key]);
            }
        }

        return new Sabre_CalDAV_CalendarObject($this->caldavBackend,$this->calendarInfo,$obj);

    }

    /**
     * Returns the full list of calendar objects
     *
     * @return array
     */
    public function getChildren() {

        $objs = $this->caldavBackend->getCalendarObjects($this->calendarInfo['id']);
        $children = array();
        foreach($objs as $obj) {
            $obj['acl'] = $this->getACL();
            // Removing the irrelivant
            foreach($obj['acl'] as $key=>$acl) {
                if ($acl['privilege'] === '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}read-free-busy') {
                    unset($obj['acl'][$key]);
                }
            }
            $children[] = new Sabre_CalDAV_CalendarObject($this->caldavBackend,$this->calendarInfo,$obj);
        }
        return $children;

    }

    /**
     * Checks if a child-node exists.
     *
     * @param string $name
     * @return bool
     */
    public function childExists($name) {

        $obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'],$name);
        if (!$obj)
            return false;
        else
            return true;

    }

    /**
     * Creates a new directory
     *
     * We actually block this, as subdirectories are not allowed in calendars.
     *
     * @param string $name
     * @return void
     */
    public function createDirectory($name) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Creating collections in calendar objects is not allowed');

    }

    /**
     * Creates a new file
     *
     * The contents of the new file must be a valid ICalendar string.
     *
     * @param string $name
     * @param resource $calendarData
     * @return string|null
     */
    public function createFile($name,$calendarData = null) {

        if (is_resource($calendarData)) {
            $calendarData = stream_get_contents($calendarData);
        }
        return $this->caldavBackend->createCalendarObject($this->calendarInfo['id'],$name,$calendarData);

    }

    /**
     * Deletes the calendar.
     *
     * @return void
     */
    public function delete() {

        $this->caldavBackend->deleteCalendar($this->calendarInfo['id']);

    }

    /**
     * Renames the calendar. Note that most calendars use the
     * {DAV:}displayname to display a name to display a name.
     *
     * @param string $newName
     * @return void
     */
    public function setName($newName) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Renaming calendars is not yet supported');

    }

    /**
     * Returns the last modification date as a unix timestamp.
     *
     * @return void
     */
    public function getLastModified() {

        return null;

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

        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner(),
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner() . '/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner() . '/calendar-proxy-write',
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner() . '/calendar-proxy-read',
                'protected' => true,
            ),
            array(
                'privilege' => '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}read-free-busy',
                'principal' => '{DAV:}authenticated',
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

        $default = Sabre_DAVACL_Plugin::getDefaultSupportedPrivilegeSet();

        // We need to inject 'read-free-busy' in the tree, aggregated under
        // {DAV:}read.
        foreach($default['aggregates'] as &$agg) {

            if ($agg['privilege'] !== '{DAV:}read') continue;

            $agg['aggregates'][] = array(
                'privilege' => '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}read-free-busy',
            );

        }
        return $default;

    }

    /**
     * Performs a calendar-query on the contents of this calendar.
     *
     * The calendar-query is defined in RFC4791 : CalDAV. Using the
     * calendar-query it is possible for a client to request a specific set of
     * object, based on contents of iCalendar properties, date-ranges and
     * iCalendar component types (VTODO, VEVENT).
     *
     * This method should just return a list of (relative) urls that match this
     * query.
     *
     * The list of filters are specified as an array. The exact array is
     * documented by Sabre_CalDAV_CalendarQueryParser.
     *
     * @param array $filters
     * @return array
     */
    public function calendarQuery(array $filters) {

        return $this->caldavBackend->calendarQuery($this->calendarInfo['id'], $filters);

    }

}
