<?php
$l=OC_L10N::get('calendar');
OC::$CLASSPATH['OC_Calendar_App'] = 'apps/calendar/lib/app.php';
OC::$CLASSPATH['OC_Calendar_Calendar'] = 'apps/calendar/lib/calendar.php';
OC::$CLASSPATH['OC_Calendar_Object'] = 'apps/calendar/lib/object.php';
OC::$CLASSPATH['OC_Calendar_Hooks'] = 'apps/calendar/lib/hooks.php';
OC::$CLASSPATH['OC_Connector_Sabre_CalDAV'] = 'apps/calendar/lib/connector_sabre.php';
OC::$CLASSPATH['OC_Calendar_Share'] = 'apps/calendar/lib/share.php';
OC::$CLASSPATH['OC_Search_Provider_Calendar'] = 'apps/calendar/lib/search.php';

//General Hooks
OCP\Util::connectHook('OC_User', 'post_createUser', 'OC_Calendar_Hooks', 'createUser');
OCP\Util::connectHook('OC_User', 'post_deleteUser', 'OC_Calendar_Hooks', 'deleteUser');
//Sharing Hooks
OCP\Util::connectHook('OC_Calendar', 'deleteEvent', 'OC_Calendar_Share', 'post_eventdelete');
OCP\Util::connectHook('OC_Calendar', 'deleteCalendar', 'OC_Calendar_Share', 'post_caldelete');

OCP\Util::addscript('calendar','loader');
OCP\Util::addscript("3rdparty", "chosen/chosen.jquery.min");
OCP\Util::addStyle("3rdparty", "chosen/chosen");
OCP\App::register( array(
  'order' => 10,
  'id' => 'calendar',
  'name' => 'Calendar' ));
OCP\App::addNavigationEntry( array(
  'id' => 'calendar_index',
  'order' => 10,
  'href' => OCP\Util::linkTo( 'calendar', 'index.php' ),
  'icon' => OCP\Util::imagePath( 'calendar', 'icon.svg' ),
  'name' => $l->t('Calendar')));
OCP\App::registerPersonal('calendar', 'settings');
OC_Search::registerProvider('OC_Search_Provider_Calendar');
