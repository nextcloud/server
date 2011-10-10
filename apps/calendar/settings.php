<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$tmpl = new OC_Template( 'calendar', 'settings');
$timezone=OC_Preferences::getValue(OC_User::getUser(),'calendar','timezone','');
$tmpl->assign('timezone',$timezone);
$tmpl->assign('timezones',DateTimeZone::listIdentifiers());

OC_Util::addScript('calendar','settings');

return $tmpl->fetchPage();
