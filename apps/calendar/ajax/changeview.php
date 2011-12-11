<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ("../../../lib/base.php");
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');
$currentview = $_GET["v"];
OC_Preferences::setValue(OC_USER::getUser(), "calendar", "currentview", $currentview);
OC_JSON::success();
?>
