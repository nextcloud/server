<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
$l10n = OC_L10N::get('calendar');
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
$output = new OCP\Template("calendar", "part.choosecalendar");
$output -> printpage();