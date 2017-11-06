<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Kamil Domanski <kdomanski@kdemail.net>
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
OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$lastConfirm = (int) \OC::$server->getSession()->get('last-password-confirm');
if ($lastConfirm < (time() - 30 * 60 + 15)) { // allow 15 seconds delay
	$l = \OC::$server->getL10N('core');
	OC_JSON::error(array( 'data' => array( 'message' => $l->t('Password confirmation is required'))));
	exit();
}

$groups = isset($_POST['groups']) ? (array)$_POST['groups'] : null;
$appIds = isset($_POST['appIds']) ? (array)$_POST['appIds'] : [];

try {
	$updateRequired = false;
	foreach($appIds as $appId) {
		$app = new OC_App();
		$appId = OC_App::cleanAppId($appId);
		$app->enable($appId, $groups);
		if(\OC_App::shouldUpgrade($appId)) {
			$updateRequired = true;
		}
	}

	OC_JSON::success(['data' => ['update_required' => $updateRequired]]);
} catch (Exception $e) {
	\OCP\Util::writeLog('core', $e->getMessage(), \OCP\Util::ERROR);
	OC_JSON::error(array("data" => array("message" => $e->getMessage()) ));
}
