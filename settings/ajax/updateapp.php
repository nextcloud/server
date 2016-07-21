<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
OCP\JSON::checkAdminUser();
OCP\JSON::callCheck();

if (!array_key_exists('appid', $_POST)) {
	OCP\JSON::error(array(
		'message' => 'No AppId given!'
	));
	return;
}

$appId = (string)$_POST['appid'];

if (!is_numeric($appId)) {
	$appId = \OC::$server->getAppConfig()->getValue($appId, 'ocsid', null);
	if ($appId === null) {
		OCP\JSON::error(array(
			'message' => 'No OCS-ID found for app!'
		));
		exit;
	}
}

$appId = OC_App::cleanAppId($appId);

$config = \OC::$server->getConfig();
$config->setSystemValue('maintenance', true);
try {
	$result = \OC\Installer::updateAppByOCSId($appId);
	$config->setSystemValue('maintenance', false);
} catch(Exception $ex) {
	$config->setSystemValue('maintenance', false);
	OC_JSON::error(array("data" => array( "message" => $ex->getMessage() )));
	return;
}

if($result !== false) {
	OC_JSON::success(array('data' => array('appid' => $appId)));
} else {
	$l = \OC::$server->getL10N('settings');
	OC_JSON::error(array("data" => array( "message" => $l->t("Couldn't update app.") )));
}
