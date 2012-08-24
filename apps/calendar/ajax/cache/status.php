<?php
/**
 * Copyright (c) 2012 Georg Ehrke <georg@ownCloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
$calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
$allcached = true;
foreach($calendars as $calendar){
	if(!OC_Calendar_Repeat::is_calendar_cached($calendar['id'])){
		$allcached = false;
	}
}
$l = new OC_L10N('calendar');
if(!$allcached){
	OCP\JSON::error(array('message'=>'Not all calendars are completely cached', 'l10n'=>$l->t('Not all calendars are completely cached')));
}else{
	OCP\JSON::success(array('message'=>'Everything seems to be completely cached', 'l10n'=>$l->t('Everything seems to be completely cached')));
}