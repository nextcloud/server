<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

// Check if we are a user
\OCP\JSON::checkLoggedIn();
\OCP\JSON::callCheck();
\OC::$server->getSession()->close();

if (isset($_GET['query'])) {
	$query = $_GET['query'];
} else {
	$query = '';
}
if (isset($_GET['inApps'])) {
	$inApps = $_GET['inApps'];
	if (is_string($inApps)) {
		$inApps = array($inApps);
	}
} else {
	$inApps = array();
}
if (isset($_GET['page'])) {
	$page = (int)$_GET['page'];
} else {
	$page = 1;
}
if (isset($_GET['size'])) {
	$size = (int)$_GET['size'];
} else {
	$size = 30;
}
if($query) {
	$result = \OC::$server->getSearch()->searchPaged($query, $inApps, $page, $size);
	OC_JSON::encodedPrint($result);
}
else {
	echo 'false';
}
