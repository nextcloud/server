<?php

/**
 * Adds caldav notification support to a backend.
 *
 * Note: This feature is experimental, and may change in between different
 * SabreDAV versions.
 *
 * Notifications are defined at:
 * http://svn.calendarserver.org/repository/calendarserver/CalendarServer/trunk/doc/Extensions/caldav-notifications.txt
 *
 * These notifications are basically a list of server-generated notifications 
 * displayed to the user. Users can dismiss notifications by deleting them.
 *
 * The primary usecase is to allow for calendar-sharing.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_CalDAV_Backend_NotificationSupport extends Sabre_CalDAV_Backend_BackendInterface {

    /**
     * Returns a list of notifications for a given principal url.
     *
     * The returned array should only consist of implementations of
     * Sabre_CalDAV_Notifications_INotificationType. 
     * 
     * @param string $principalUri
     * @return array 
     */
    public function getNotificationsForPrincipal($principalUri);

    /**
     * This deletes a specific notifcation.
     *
     * This may be called by a client once it deems a notification handled. 
     * 
     * @param string $principalUri 
     * @param Sabre_CalDAV_Notifications_INotificationType $notification 
     * @return void
     */
    public function deleteNotification($principalUri, Sabre_CalDAV_Notifications_INotificationType $notification); 

}
