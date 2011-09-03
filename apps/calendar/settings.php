<?php

$tmpl = new OC_Template( 'calendar', 'settings');
$timezone=OC_Preferences::getValue(OC_User::getUser(),'calendar','timezone','');
$tmpl->assign('timezone',$timezone);
$tmpl->assign('timezones',DateTimeZone::listIdentifiers());

OC_Util::addScript('calendar','settings');

return $tmpl->fetchPage();
