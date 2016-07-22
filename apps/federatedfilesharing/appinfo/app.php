<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
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

use OCA\FederatedFileSharing\Notifier;

$app = new \OCA\FederatedFileSharing\AppInfo\Application();
$l = \OC::$server->getL10N('files_sharing');
$eventDispatcher = \OC::$server->getEventDispatcher();

$app->registerSettings();

$manager = \OC::$server->getNotificationManager();
$manager->registerNotifier(function() {
	return new Notifier(
		\OC::$server->getL10NFactory()
	);
}, function() use ($l) {
	return [
		'id' => 'files_sharing',
		'name' => $l->t('Federated sharing'),
	];
});

$federatedShareProvider = $app->getFederatedShareProvider();

$eventDispatcher->addListener(
	'OCA\Files::loadAdditionalScripts',
	function() use ($federatedShareProvider) {
		if ($federatedShareProvider->isIncomingServer2serverShareEnabled()) {
			\OCP\Util::addScript('federatedfilesharing', 'external');
		}
	}
);
