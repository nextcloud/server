<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
OC_JSON::checkLoggedIn();
$firstday = OCP\Config::getUserValue( OCP\USER::getUser(), 'calendar', 'firstday', 'mo');
OC_JSON::encodedPrint(array('firstday' => $firstday));
?> 
