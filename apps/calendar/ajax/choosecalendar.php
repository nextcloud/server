<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');
$l10n = new OC_L10N('calendar');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');
$output = new OC_TEMPLATE("calendar", "part.choosecalendar");
$output -> printpage();
?>
