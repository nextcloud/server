<?php
$l=new OC_L10N('calendar');
OC::$CLASSPATH['OC_Calendar_App'] = 'apps/calendar/lib/app.php';
OC::$CLASSPATH['OC_Calendar_Calendar'] = 'apps/calendar/lib/calendar.php';
OC::$CLASSPATH['OC_Calendar_Object'] = 'apps/calendar/lib/object.php';
OC::$CLASSPATH['OC_Calendar_Hooks'] = 'apps/calendar/lib/hooks.php';
OC::$CLASSPATH['OC_Connector_Sabre_CalDAV'] = 'apps/calendar/lib/connector_sabre.php';
OC_HOOK::connect('OC_User', 'post_deleteUser', 'OC_Calendar_Hooks', 'deleteUser');
OC_Util::addScript('calendar','loader');
OC_App::register( array( 
  'order' => 10,
  'id' => 'calendar',
  'name' => 'Calendar' ));
OC_App::addNavigationEntry( array( 
  'id' => 'calendar_index',
  'order' => 10,
  'href' => OC_Helper::linkTo( 'calendar', 'index.php' ),
  'icon' => OC_Helper::imagePath( 'calendar', 'icon.svg' ),
  'name' => $l->t('Calendar')));
OC_App::registerPersonal('calendar', 'settings');
require_once('apps/calendar/lib/search.php');
