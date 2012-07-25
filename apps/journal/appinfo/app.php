<?php
$l=new OC_L10N('journal');
OC::$CLASSPATH['OC_Calendar_Calendar'] = 'calendar/lib/calendar.php';
OC::$CLASSPATH['OC_Journal_App'] = 'journal/lib/app.php';
OC::$CLASSPATH['OC_Search_Provider_Journal'] = 'journal/lib/search.php';
OC::$CLASSPATH['OC_Journal_Hooks'] = 'journal/lib/hooks.php';

OCP\Util::connectHook('OC_Task', 'taskCompleted', 'OC_Journal_Hooks', 'taskToJournalEntry');

OCP\App::addNavigationEntry( array(
  'id' => 'journal_index',
  'order' => 11,
  'href' => OCP\Util::linkTo( 'journal', 'index.php' ),
  'icon' => OCP\Util::imagePath( 'journal', 'journal.png' ),
  'name' => $l->t('Journal')));

OC_Search::registerProvider('OC_Search_Provider_Journal');
OCP\App::registerPersonal('journal','settings');