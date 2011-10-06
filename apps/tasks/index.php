<?php
/*************************************************
 * ownCloud - Tasks Plugin                        *
 *                                                *
 * (c) Copyright 2011 Bart Visscher               *
 * This file is licensed under the Affero General *
 * Public License version 3 or later.             *
 * See the COPYING-README file.                   *
 *************************************************/

require_once ('../../lib/base.php');
OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('tasks');

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), true);
if( count($calendars) == 0){
	header('Location: ' . OC_HELPER::linkTo('calendar', 'index.php'));
	exit;
}

$id = isset( $_GET['id'] ) ? $_GET['id'] : null;

$tasks = array();
foreach( $calendars as $calendar ){
        $calendar_tasks = OC_Calendar_Object::all($calendar['id']);
        foreach( $calendar_tasks as $task ){
                if($task['objecttype']!='VTODO'){
                        continue;
                }
                if(is_null($task['summary'])){
                        continue;
                }
                $tasks[] = array( 'name' => $task['summary'], 'id' => $task['id'] );
        }
}

if( !is_null($id) || count($tasks)){
        if(is_null($id)) $id = $tasks[0]['id'];
        $task = OC_Calendar_Object::find($id);
        $details = Sabre_VObject_Reader::read($task['calendardata'])->VTODO;
}

OC_UTIL::addScript('tasks', 'tasks');
OC_UTIL::addStyle('tasks', 'style');
OC_APP::setActiveNavigationEntry('tasks_index');
$output = new OC_Template('tasks', 'tasks', 'user');
$output->assign('tasks', $tasks);
$output->assign('details', $details);
$output->assign('id',$id);
$output -> printPage();
