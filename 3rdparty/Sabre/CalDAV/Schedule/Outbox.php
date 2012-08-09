<?php

/**
 * The CalDAV scheduling outbox
 *
 * The outbox is mainly used as an endpoint in the tree for a client to do
 * free-busy requests. This functionality is completely handled by the
 * Scheduling plugin, so this object is actually mostly static.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Schedule_Outbox extends Sabre_DAV_Directory implements Sabre_CalDAV_Schedule_IOutbox {

    /**
     * The principal Uri
     *
     * @var string
     */
    protected $principalUri;

    /**
     * Constructor
     *
     * @param string $principalUri
     */
    public function __construct($principalUri) {

        $this->principalUri = $principalUri;

    }

    /**
     * Returns the name of the node.
     *
     * This is used to generate the url.
     *
     * @return string
     */
    public function getName() {

        return 'outbox';

    }

    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    public function getChildren() {

        return array();

    }

    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner() {

        return $this->principalUri;

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
                'privilege' => '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-query-freebusy',
                'principal' => $this->getOwner(),
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
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

        throw new Sabre_DAV_Exception_MethodNotAllowed('You\'re not allowed to update the ACL');

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
        $default['aggregates'][] = array(
            'privilege' => '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-query-freebusy',
        );

        return $default;

    }

}
