<?php
$l=OC_L10N::get('calendar');
OC::$CLASSPATH['OC_Calendar_App'] = 'apps/calendar/lib/app.php';
OC::$CLASSPATH['OC_Calendar_Calendar'] = 'apps/calendar/lib/calendar.php';
OC::$CLASSPATH['OC_Calendar_Object'] = 'apps/calendar/lib/object.php';
OC::$CLASSPATH['OC_Calendar_Hooks'] = 'apps/calendar/lib/hooks.php';
OC::$CLASSPATH['OC_Connector_Sabre_CalDAV'] = 'apps/calendar/lib/connector_sabre.php';
OC::$CLASSPATH['OC_Calendar_Repeat'] = 'apps/calendar/lib/repeat.php';
OC::$CLASSPATH['OC_Calendar_Share'] = 'apps/calendar/lib/share.php';
OC::$CLASSPATH['OC_Search_Provider_Calendar'] = 'apps/calendar/lib/search.php';
OC::$CLASSPATH['OC_Calendar_Export'] = 'apps/calendar/lib/export.php';
OC::$CLASSPATH['OC_Calendar_Import'] = 'apps/calendar/lib/import.php';
OC::$CLASSPATH['OC_Share_Backend_Calendar'] = 'apps/calendar/lib/share/calendar.php';
OC::$CLASSPATH['OC_Share_Backend_Event'] = 'apps/calendar/lib/share/event.php';
//General Hooks
OCP\Util::connectHook('OC_User', 'post_createUser', 'OC_Calendar_Hooks', 'createUser');
OCP\Util::connectHook('OC_User', 'post_deleteUser', 'OC_Calendar_Hooks', 'deleteUser');
//Repeating Events Hooks
OCP\Util::connectHook('OC_Calendar', 'addEvent', 'OC_Calendar_Repeat', 'generate');
OCP\Util::connectHook('OC_Calendar', 'editEvent', 'OC_Calendar_Repeat', 'update');
OCP\Util::connectHook('OC_Calendar', 'deleteEvent', 'OC_Calendar_Repeat', 'clean');
OCP\Util::connectHook('OC_Calendar', 'moveEvent', 'OC_Calendar_Repeat', 'update');
OCP\Util::connectHook('OC_Calendar', 'deleteCalendar', 'OC_Calendar_Repeat', 'cleanCalendar');
//Sharing Hooks
OCP\Util::connectHook('OC_Calendar', 'deleteEvent', 'OC_Calendar_Share', 'post_eventdelete');
OCP\Util::connectHook('OC_Calendar', 'deleteCalendar', 'OC_Calendar_Share', 'post_caldelete');
OCP\Util::addscript('calendar','loader');
OCP\Util::addscript("3rdparty", "chosen/chosen.jquery.min");
OCP\Util::addStyle("3rdparty", "chosen/chosen");
OCP\Util::addStyle('3rdparty/miniColors', 'jquery.miniColors');
OCP\Util::addscript('3rdparty/miniColors', 'jquery.miniColors.min');
OCP\App::addNavigationEntry( array(
  'id' => 'calendar_index',
  'order' => 10,
  'href' => OCP\Util::linkTo( 'calendar', 'index.php' ),
  'icon' => OCP\Util::imagePath( 'calendar', 'icon.svg' ),
  'name' => $l->t('Calendar')));
OCP\App::registerPersonal('calendar', 'settings');
OC_Search::registerProvider('OC_Search_Provider_Calendar');
OCP\Share::registerBackend('calendar', 'OC_Share_Backend_Calendar');
OCP\Share::registerBackend('event', 'OC_Share_Backend_Event');
