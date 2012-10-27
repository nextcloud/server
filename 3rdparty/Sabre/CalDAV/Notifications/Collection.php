<?php

/**
 * This node represents a list of notifications.
 *
 * It provides no additional functionality, but you must implement this
 * interface to allow the Notifications plugin to mark the collection
 * as a notifications collection.
 *
 * This collection should only return Sabre_CalDAV_Notifications_INode nodes as
 * its children.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Notifications_Collection extends Sabre_DAV_Collection implements Sabre_CalDAV_Notifications_ICollection, Sabre_DAVACL_IACL {

    /**
     * The notification backend
     *
     * @var Sabre_CalDAV_Backend_NotificationSupport
     */
    protected $caldavBackend;

    /**
     * Principal uri
     *
     * @var string
     */
    protected $principalUri;

    /**
     * Constructor
     *
     * @param Sabre_CalDAV_Backend_NotificationSupport $caldavBackend
     * @param string $principalUri
     */
    public function __construct(Sabre_CalDAV_Backend_NotificationSupport $caldavBackend, $principalUri) {

        $this->caldavBackend = $caldavBackend;
        $this->principalUri = $principalUri;

    }

    /**
     * Returns all notifications for a principal
     *
     * @return array
     */
    public function getChildren() {

        $children = array();
        $notifications = $this->caldavBackend->getNotificationsForPrincipal($this->principalUri);

        foreach($notifications as $notification) {

            $children[] = new Sabre_CalDAV_Notifications_Node(
                $this->caldavBackend,
                $this->principalUri,
                $notification
            );
        }

        return $children;

    }

    /**
     * Returns the name of this object
     *
     * @return string
     */
    public function getName() {

        return 'notifications';

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
                'principal' => $this->getOwner(),
                'privilege' => '{DAV:}read',
                'protected' => true,
            ),
            array(
                'principal' => $this->getOwner(),
                'privilege' => '{DAV:}write',
                'protected' => true,
            )
        );

    }

    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's as an array argument.
     *
     * @param array $acl
     * @return void
     */
    public function setACL(array $acl) {

        throw new Sabre_DAV_Exception_NotImplemented('Updating ACLs is not implemented here');

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
