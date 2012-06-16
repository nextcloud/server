<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
OCP\JSON::checkLoggedIn();
$timeformat = OCP\Config::getUserValue( OCP\USER::getUser(), 'calendar', 'timeformat', "24");
OCP\JSON::encodedPrint(array("timeformat" => $timeformat));