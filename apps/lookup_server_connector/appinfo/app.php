<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$dispatcher = \OC::$server->getEventDispatcher();

$dispatcher->addListener('OC\AccountManager::userUpdated', function(\Symfony\Component\EventDispatcher\GenericEvent $event) {
	$user = $event->getSubject();

	$keyManager = new \OC\Security\IdentityProof\Manager(
		\OC::$server->getAppDataDir('identityproof'),
		\OC::$server->getCrypto()
	);
	$updateLookupServer = new \OCA\LookupServerConnector\UpdateLookupServer(
		new \OC\Accounts\AccountManager(\OC::$server->getDatabaseConnection(), \OC::$server->getEventDispatcher()),
		\OC::$server->getConfig(),
		\OC::$server->getSecureRandom(),
		\OC::$server->getHTTPClientService(),
		$keyManager,
		new \OC\Security\IdentityProof\Signer(
			$keyManager,
			new \OC\AppFramework\Utility\TimeFactory(),
			\OC::$server->getUserManager()
		),
		\OC::$server->getJobList()
	);
	$updateLookupServer->userUpdated($user);
});
