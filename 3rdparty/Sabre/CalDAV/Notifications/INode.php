<?php

/**
 * This node represents a single notification.
 *
 * The signature is mostly identical to that of Sabre_DAV_IFile, but the get() method
 * MUST return an xml document that matches the requirements of the
 * 'caldav-notifications.txt' spec.
 *
 * For a complete example, check out the Notification class, which contains
 * some helper functions.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_CalDAV_Notifications_INode {

    /**
     * This method must return an xml element, using the
     * Sabre_CalDAV_Notifications_INotificationType classes.
     *
     * @return Sabre_DAVNotification_INotificationType
     */
    function getNotificationType();

    /**
     * Returns the etag for the notification.
     *
     * The etag must be surrounded by litteral double-quotes.
     *
     * @return string
     */
    function getETag();

}
