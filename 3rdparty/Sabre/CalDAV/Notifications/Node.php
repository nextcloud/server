<?php

/**
 * This node represents a single notification.
 *
 * The signature is mostly identical to that of Sabre_DAV_IFile, but the get() method
 * MUST return an xml document that matches the requirements of the
 * 'caldav-notifications.txt' spec.

 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Notifications_Node extends Sabre_DAV_File implements Sabre_CalDAV_Notifications_INode, Sabre_DAVACL_IACL {

    /**
     * The notification backend
     *
     * @var Sabre_CalDAV_Backend_NotificationSupport
     */
    protected $caldavBackend;

    /**
     * The actual notification
     *
     * @var Sabre_CalDAV_Notifications_INotificationType
     */
    protected $notification;

    /**
     * Owner principal of the notification
     *
     * @var string
     */
    protected $principalUri;

    /**
     * Constructor
     *
     * @param Sabre_CalDAV_Backend_NotificationSupport $caldavBackend
     * @param string $principalUri
     * @param Sabre_CalDAV_Notifications_INotificationType $notification
     */
    public function __construct(Sabre_CalDAV_Backend_NotificationSupport $caldavBackend, $principalUri, Sabre_CalDAV_Notifications_INotificationType $notification) {

        $this->caldavBackend = $caldavBackend;
        $this->principalUri = $principalUri;
        $this->notification = $notification;

    }

    /**
     * Returns the path name for this notification
     *
     * @return id
     */
    public function getName() {

        return $this->notification->getId() . '.xml';

    }

    /**
     * Returns the etag for the notification.
     *
     * The etag must be surrounded by litteral double-quotes.
     *
     * @return string
     */
    public function getETag() {

        return $this->notification->getETag();

    }

    /**
     * This method must return an xml element, using the
     * Sabre_CalDAV_Notifications_INotificationType classes.
     *
     * @return Sabre_DAVNotification_INotificationType
     */
    public function getNotificationType() {

        return $this->notification;

    }

    /**
     * Deletes this notification
     *
     * @return void
     */
    public function delete() {

        $this->caldavBackend->deleteNotification($this->getOwner(), $this->notification);

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
