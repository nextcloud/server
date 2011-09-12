<?php
$l=new OC_L10N('tasks');
OC::$CLASSPATH['OC_Calendar_Calendar'] = 'apps/calendar/lib/calendar.php';

OC_App::register( array(
  'order' => 11,
  'id' => 'tasks',
  'name' => 'Tasks' ));

OC_App::addNavigationEntry( array(
  'id' => 'tasks_index',
  'order' => 11,
  'href' => OC_Helper::linkTo( 'tasks', 'index.php' ),
  //'icon' => OC_Helper::imagePath( 'tasks', 'icon.png' ),
  'name' => $l->t('Tasks')));
