<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownClouddev at georgswebsite.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
$l10n = OC_L10N::get('calendar');
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
$tmpl = new OCP\Template('calendar', 'part.choosecalendar');
$tmpl->printpage();