<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\DAV\AppInfo;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CardDAV\ContactsManager;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\HookManager;
use \OCP\AppFramework\App;
use OCP\Contacts\IManager;
use Symfony\Component\EventDispatcher\GenericEvent;

class Application extends App {

	/**
	 * Application constructor.
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
				$c->query('SyncService'),
				$c->query('CalDavBackend'),
				$c->query('CardDavBackend')
			);
		});

		$container->registerService('SyncService', function($c) {
			/** @var IAppContainer $c */
			return new SyncService(
				$c->query('CardDavBackend'),
				$c->getServer()->getUserManager(),
				$c->getServer()->getLogger()
			);
		});

		$container->registerService('CardDavBackend', function($c) {
			/** @var IAppContainer $c */
			$db = $c->getServer()->getDatabaseConnection();
			$dispatcher = $c->getServer()->getEventDispatcher();
			$principal = new Principal(
				$c->getServer()->getUserManager(),
				$c->getServer()->getGroupManager()
			);
			return new CardDavBackend($db, $principal, $c->getServer()->getUserManager(), $dispatcher);
		});

		$container->registerService('CalDavBackend', function($c) {
			/** @var IAppContainer $c */
			$db = $c->getServer()->getDatabaseConnection();
			$config = $c->getServer()->getConfig();
			$principal = new Principal(
				$c->getServer()->getUserManager(),
				$c->getServer()->getGroupManager()
			);
			return new CalDavBackend($db, $principal, $c->getServer()->getUserManager(), $config);
		});

		$container->registerService('BirthdayService', function($c) {
			/** @var IAppContainer $c */
			$g = new GroupPrincipalBackend(
				$c->getServer()->getGroupManager()
			);
			return new BirthdayService(
				$c->query('CalDavBackend'),
				$c->query('CardDavBackend'),
				$g
			);
		});

		$container->registerService('OCA\DAV\Migration\Classification', function ($c) {
			/** @var IAppContainer $c */
			return new Classification(
				$c->query('CalDavBackend'),
				$c->getServer()->getUserManager()
			);
		});

		$container->registerService('OCA\DAV\Migration\GenerateBirthdays', function ($c) {
			/** @var IAppContainer $c */
			/** @var BirthdayService $b */
			$b = $c->query('BirthdayService');
			return new GenerateBirthdays(
				$b,
				$c->getServer()->getUserManager()
			);
		});
	}

	/**
	 * @param IManager $contactsManager
	 * @param string $userID
	 */
	public function setupContactsProvider(IManager $contactsManager, $userID) {
		/** @var ContactsManager $cm */
		$cm = $this->getContainer()->query(ContactsManager::class);
		$urlGenerator = $this->getContainer()->getServer()->getURLGenerator();
		$cm->setupContactsProvider($contactsManager, $userID, $urlGenerator);
	}

	public function registerHooks() {
		/** @var HookManager $hm */
		$hm = $this->getContainer()->query(HookManager::class);
		$hm->setup();

		$listener = function($event) {
			if ($event instanceof GenericEvent) {
				/** @var BirthdayService $b */
				$b = $this->getContainer()->query(BirthdayService::class);
				$b->onCardChanged(
					$event->getArgument('addressBookId'),
					$event->getArgument('cardUri'),
					$event->getArgument('cardData')
				);
			}
		};

		$dispatcher = $this->getContainer()->getServer()->getEventDispatcher();
		$dispatcher->addListener('\OCA\DAV\CardDAV\CardDavBackend::createCard', $listener);
		$dispatcher->addListener('\OCA\DAV\CardDAV\CardDavBackend::updateCard', $listener);
		$dispatcher->addListener('\OCA\DAV\CardDAV\CardDavBackend::deleteCard', function($event) {
			if ($event instanceof GenericEvent) {
				/** @var BirthdayService $b */
				$b = $this->getContainer()->query(BirthdayService::class);
				$b->onCardDeleted(
					$event->getArgument('addressBookId'),
					$event->getArgument('cardUri')
				);
			}
		});
	}

	public function getSyncService() {
		return $this->getContainer()->query(SyncService::class);
	}

}
