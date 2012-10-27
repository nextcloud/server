<?php

/**
 * Sabre_CalDAV includes file
 *
 * Including this file will automatically include all files from the
 * Sabre_CalDAV package.
 *
 * This often allows faster loadtimes, as autoload-speed is often quite slow.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

// Begin includes
include __DIR__ . '/Backend/BackendInterface.php';
include __DIR__ . '/Backend/NotificationSupport.php';
include __DIR__ . '/Backend/SharingSupport.php';
include __DIR__ . '/CalendarQueryParser.php';
include __DIR__ . '/CalendarQueryValidator.php';
include __DIR__ . '/CalendarRootNode.php';
include __DIR__ . '/Exception/InvalidComponentType.php';
include __DIR__ . '/ICalendar.php';
include __DIR__ . '/ICalendarObject.php';
include __DIR__ . '/ICSExportPlugin.php';
include __DIR__ . '/IShareableCalendar.php';
include __DIR__ . '/ISharedCalendar.php';
include __DIR__ . '/Notifications/ICollection.php';
include __DIR__ . '/Notifications/INode.php';
include __DIR__ . '/Notifications/INotificationType.php';
include __DIR__ . '/Notifications/Node.php';
include __DIR__ . '/Notifications/Notification/Invite.php';
include __DIR__ . '/Notifications/Notification/InviteReply.php';
include __DIR__ . '/Notifications/Notification/SystemStatus.php';
include __DIR__ . '/Plugin.php';
include __DIR__ . '/Principal/Collection.php';
include __DIR__ . '/Principal/ProxyRead.php';
include __DIR__ . '/Principal/ProxyWrite.php';
include __DIR__ . '/Principal/User.php';
include __DIR__ . '/Property/AllowedSharingModes.php';
include __DIR__ . '/Property/Invite.php';
include __DIR__ . '/Property/ScheduleCalendarTransp.php';
include __DIR__ . '/Property/SupportedCalendarComponentSet.php';
include __DIR__ . '/Property/SupportedCalendarData.php';
include __DIR__ . '/Property/SupportedCollationSet.php';
include __DIR__ . '/Schedule/IMip.php';
include __DIR__ . '/Schedule/IOutbox.php';
include __DIR__ . '/Schedule/Outbox.php';
include __DIR__ . '/SharingPlugin.php';
include __DIR__ . '/UserCalendars.php';
include __DIR__ . '/Version.php';
include __DIR__ . '/Backend/Abstract.php';
include __DIR__ . '/Backend/PDO.php';
include __DIR__ . '/Calendar.php';
include __DIR__ . '/CalendarObject.php';
include __DIR__ . '/Notifications/Collection.php';
include __DIR__ . '/ShareableCalendar.php';
include __DIR__ . '/SharedCalendar.php';
// End includes
