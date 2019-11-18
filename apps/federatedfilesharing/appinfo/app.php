<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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
use OCA\FederatedFileSharing\AppInfo\Application;

$app = \OC::$server->query(Application::class);
$eventDispatcher = \OC::$server->getEventDispatcher();

$manager = \OC::$server->getNotificationManager();
$manager->registerNotifierService(Notifier::class);

$federatedShareProvider = $app->getFederatedShareProvider();

$eventDispatcher->addListener(
	'OCA\Files::loadAdditionalScripts',
	function() use ($federatedShareProvider) {
		if ($federatedShareProvider->isIncomingServer2serverShareEnabled()) {
			\OCP\Util::addScript('federatedfilesharing', 'external');
		}
	}
);
