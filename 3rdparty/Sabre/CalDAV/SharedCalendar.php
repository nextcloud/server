<?php

/**
 * This object represents a CalDAV calendar that is shared by a different user.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_SharedCalendar extends Sabre_CalDAV_Calendar implements Sabre_CalDAV_ISharedCalendar {

    /**
     * Constructor
     *
     * @param Sabre_DAVACL_IPrincipalBackend $principalBackend
     * @param Sabre_CalDAV_Backend_BackendInterface $caldavBackend
     * @param array $calendarInfo
     */
    public function __construct(Sabre_DAVACL_IPrincipalBackend $principalBackend, Sabre_CalDAV_Backend_BackendInterface $caldavBackend, $calendarInfo) {

        $required = array(
            '{http://calendarserver.org/ns/}shared-url',
            '{http://sabredav.org/ns}owner-principal',
            '{http://sabredav.org/ns}read-only',
        );
        foreach($required as $r) {
            if (!isset($calendarInfo[$r])) {
                throw new InvalidArgumentException('The ' . $r . ' property must be specified for SharedCalendar(s)');
            }
        }

        parent::__construct($principalBackend, $caldavBackend, $calendarInfo);

    }

    /**
     * This method should return the url of the owners' copy of the shared
     * calendar.
     *
     * @return string
     */
    public function getSharedUrl() {

        return $this->calendarInfo['{http://calendarserver.org/ns/}shared-url'];

    }

    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner() {

        return $this->calendarInfo['{http://sabredav.org/ns}owner-principal'];

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

        // The top-level ACL only contains access information for the true
        // owner of the calendar, so we need to add the information for the
        // sharee.
        $acl = parent::getACL();
        $acl[] = array(
            'privilege' => '{DAV:}read',
            'principal' => $this->calendarInfo['principaluri'],
            'protected' => true,
        );
        if (!$this->calendarInfo['{http://sabredav.org/ns}read-only']) {
            $acl[] = array(
                'privilege' => '{DAV:}write',
                'principal' => $this->calendarInfo['principaluri'],
                'protected' => true,
            );
        }
        return $acl;

    }


}
