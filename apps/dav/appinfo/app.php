<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\Connector\Sabre\Principal;

\OC::$server->registerService('CardDAVSyncService', function() {
	$principalBackend = new Principal(
		$this->config,
		$this->userManager
	);

	$backend = new CardDavBackend($this->dbConnection, $principalBackend);

	return new SyncService($backend);
});

$cm = \OC::$server->getContactsManager();
$cm->register(function() use ($cm) {
	$userId = \OC::$server->getUserSession()->getUser()->getUID();
	$app = new \OCA\Dav\AppInfo\Application();
	$app->setupContactsProvider($cm, $userId);
});
