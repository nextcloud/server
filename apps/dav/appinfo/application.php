<?php
/**
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
namespace OCA\Dav\AppInfo;

use OCA\DAV\CardDAV\ContactsManager;
use OCA\DAV\CardDAV\SyncJob;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\HookManager;
use \OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\Contacts\IManager;

class Application extends App {

	/**
	 * Application constructor.
	 *
	 * @param array $urlParams
	 */
	public function __construct (array $urlParams=array()) {
		parent::__construct('dav', $urlParams);

		$container = $this->getContainer();
		$container->registerService('ContactsManager', function($c) {
			/** @var IAppContainer $c */
			return new ContactsManager(
				$c->query('CardDavBackend')
			);
		});

		$container->registerService('HookManager', function($c) {
			/** @var IAppContainer $c */
			return new HookManager(
				$c->getServer()->getUserManager(),
				$c->query('SyncService')
			);
		});

		$container->registerService('SyncService', function($c) {
			/** @var IAppContainer $c */
			return new SyncService(
				$c->query('CardDavBackend'),
				$c->getServer()->getUserManager()
			);
		});

		$container->registerService('CardDavBackend', function($c) {
			/** @var IAppContainer $c */
			$db = $c->getServer()->getDatabaseConnection();
			$logger = $c->getServer()->getLogger();
			$principal = new \OCA\DAV\Connector\Sabre\Principal(
				$c->getServer()->getUserManager(),
				$c->getServer()->getGroupManager()
			);
			return new \OCA\DAV\CardDAV\CardDavBackend($db, $principal, $logger);
		});

	}

	/**
	 * @param IManager $contactsManager
	 * @param string $userID
	 */
	public function setupContactsProvider(IManager $contactsManager, $userID) {
		/** @var ContactsManager $cm */
		$cm = $this->getContainer()->query('ContactsManager');
		$cm->setupContactsProvider($contactsManager, $userID);
	}

	public function registerHooks() {
		/** @var HookManager $hm */
		$hm = $this->getContainer()->query('HookManager');
		$hm->setup();
	}

	public function getSyncService() {
		return $this->getContainer()->query('SyncService');
	}

	public function setupCron() {
		$jl = $this->getContainer()->getServer()->getJobList();
		$jl->add(new SyncJob());
	}

}
