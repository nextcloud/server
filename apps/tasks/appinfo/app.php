<?php
$l=new OC_L10N('tasks');
OC::$CLASSPATH['OC_Calendar_Calendar'] = 'apps/calendar/lib/calendar.php';
OC::$CLASSPATH['OC_Task_App'] = 'apps/tasks/lib/app.php';

OCP\App::addNavigationEntry( array(
  'id' => 'tasks_index',
  'order' => 11,
  'href' => OCP\Util::linkTo( 'tasks', 'index.php' ),
  'icon' => OCP\Util::imagePath( 'tasks', 'icon.png' ),
  'name' => $l->t('Tasks')));
