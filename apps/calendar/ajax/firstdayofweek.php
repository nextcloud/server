<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
$firstdayofweek = OC_Preferences::getValue( OC_User::getUser(), 'calendar', 'firstdayofweek', "1");
OC_JSON::encodedPrint(array("firstdayofweek" => $firstdayofweek));
?>