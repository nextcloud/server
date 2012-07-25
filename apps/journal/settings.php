<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();

$errors = array();

$required_apps = array(
    array('id' => 'tal', 'name' => 'TAL Page Templates'),
    array('id' => 'journal', 'name' => 'Journal'),
    array('id' => 'contacts', 'name' => 'Contacts'),
);
foreach($required_apps as $app) {
	if(!OCP\App::isEnabled($app['id'])) {
		$error = (string)$l->t('The %%s app isn\'t enabled! Please enable it here: <strong><a href="%%s?appid=%%s">Enable %%s app</a></strong>');
		$errors[] = sprintf($error, $app['name'],OCP\Util::linkTo('settings', 'apps'), $app['id'], $app['name']);
	}
}

$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser(), true);
if( count($calendars) == 0 ) {
	$error = (string)$l->t('You have no calendars. Please add one at the <strong><a href="%%s">Calendar app</a></strong>');
	$errors[] = sprintf($error, OCP\Util::linkTo('calendar', 'index.php'));
}

if(count($errors) > 0) {
	$tmpl = new OCP\Template('journal', 'rtfm');
	$tmpl->assign('errors',$errors, false);
} else {
	$cid = OCP\Config::getUserValue(OCP\User::getUser(), 'journal', 'default_calendar', null);
	OCP\Util::addScript('journal', 'settings');
	$tmpl = new OC_TALTemplate('journal', 'settings', 'user');
	$tmpl->assign('calendars', $calendars);
		$tmpl->assign('cid', $cid);
}

return $tmpl->fetchPage();

?>
