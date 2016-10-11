<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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

if(\OC::$server->getConfig()->getSystemValue('updatechecker', true) === true) {
	$updater = new \OC\Updater\VersionCheck(
		\OC::$server->getHTTPClientService(),
		\OC::$server->getConfig()
	);
	$updateChecker = new \OCA\UpdateNotification\UpdateChecker(
		$updater
	);

	$userObject = \OC::$server->getUserSession()->getUser();
	if($userObject !== null) {
		if(\OC::$server->getGroupManager()->isAdmin($userObject->getUID())) {
			if($updateChecker->getUpdateState() !== []) {
				\OCP\Util::addScript('updatenotification', 'notification');
				OC_Hook::connect('\OCP\Config', 'js', $updateChecker, 'getJavaScript');
			}
		}
	}

	$manager = \OC::$server->getNotificationManager();
	$manager->registerNotifier(function() use ($manager) {
		return new \OCA\UpdateNotification\Notification\Notifier(
			$manager,
			\OC::$server->getL10NFactory()
		);
	}, function() {
		$l = \OC::$server->getL10N('updatenotification');
		return [
			'id' => 'updatenotification',
			'name' => $l->t('Update notifications'),
		];
	});
}
