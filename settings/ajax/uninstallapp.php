<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

$lastConfirm = (int) \OC::$server->getSession()->get('last-password-confirm');
if ($lastConfirm < (time() - 30 * 60 + 15)) { // allow 15 seconds delay
	$l = \OC::$server->getL10N('core');
	OC_JSON::error(array( 'data' => array( 'message' => $l->t('Password confirmation is required'))));
	exit();
}

if (!array_key_exists('appid', $_POST)) {
	OC_JSON::error();
	exit;
}

$appId = (string)$_POST['appid'];
$appId = OC_App::cleanAppId($appId);

// FIXME: move to controller
/** @var \OC\Installer $installer */
$installer = \OC::$server->query(\OC\Installer::class);
$result = $installer->removeApp($app);
if($result !== false) {
	// FIXME: Clear the cache - move that into some sane helper method
	\OC::$server->getMemCacheFactory()->createDistributed('settings')->remove('listApps-0');
	\OC::$server->getMemCacheFactory()->createDistributed('settings')->remove('listApps-1');
	OC_JSON::success(array('data' => array('appid' => $appId)));
} else {
	$l = \OC::$server->getL10N('settings');
	OC_JSON::error(array('data' => array( 'message' => $l->t("Couldn't remove app.") )));
}
