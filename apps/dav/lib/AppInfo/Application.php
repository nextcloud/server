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

use OCA\DAV\CalDAV\Activity\Backend;
use OCA\DAV\CalDAV\Activity\Extension;
use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\Capabilities;
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
	public function __construct() {
		parent::__construct('dav');

		/*
		 * Register capabilities
		 */
		$this->getContainer()->registerCapability(Capabilities::class);
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

		$dispatcher->addListener('\OCA\DAV\CalDAV\CalDavBackend::createCalendar', function(GenericEvent $event) {
			/** @var Backend $backend */
			$backend = $this->getContainer()->query(Backend::class);
			$backend->onCalendarAdd(
				$event->getArgument('calendarData')
			);
		});
		$dispatcher->addListener('\OCA\DAV\CalDAV\CalDavBackend::updateCalendar', function(GenericEvent $event) {
			/** @var Backend $backend */
			$backend = $this->getContainer()->query(Backend::class);
			$backend->onCalendarUpdate(
				$event->getArgument('calendarData'),
				$event->getArgument('shares'),
				$event->getArgument('propertyMutations')
			);
		});
		$dispatcher->addListener('\OCA\DAV\CalDAV\CalDavBackend::deleteCalendar', function(GenericEvent $event) {
			/** @var Backend $backend */
			$backend = $this->getContainer()->query(Backend::class);
			$backend->onCalendarDelete(
				$event->getArgument('calendarData'),
				$event->getArgument('shares')
			);
		});
		$dispatcher->addListener('\OCA\DAV\CalDAV\CalDavBackend::updateShares', function(GenericEvent $event) {
			/** @var Backend $backend */
			$backend = $this->getContainer()->query(Backend::class);
			$backend->onCalendarUpdateShares(
				$event->getArgument('calendarData'),
				$event->getArgument('shares'),
				$event->getArgument('add'),
				$event->getArgument('remove')
			);
		});

		$listener = function(GenericEvent $event, $eventName) {
			/** @var Backend $backend */
			$backend = $this->getContainer()->query(Backend::class);

			$subject = Extension::SUBJECT_OBJECT_ADD;
			if ($eventName === '\OCA\DAV\CalDAV\CalDavBackend::updateCalendarObject') {
				$subject = Extension::SUBJECT_OBJECT_UPDATE;
			} else if ($eventName === '\OCA\DAV\CalDAV\CalDavBackend::deleteCalendarObject') {
				$subject = Extension::SUBJECT_OBJECT_DELETE;
			}
			$backend->onTouchCalendarObject(
				$subject,
				$event->getArgument('calendarData'),
				$event->getArgument('shares'),
				$event->getArgument('objectData')
			);
		};
		$dispatcher->addListener('\OCA\DAV\CalDAV\CalDavBackend::createCalendarObject', $listener);
		$dispatcher->addListener('\OCA\DAV\CalDAV\CalDavBackend::updateCalendarObject', $listener);
		$dispatcher->addListener('\OCA\DAV\CalDAV\CalDavBackend::deleteCalendarObject', $listener);

		$aM = $this->getContainer()->getServer()->getActivityManager();
		$aM->registerExtension(function() {
			return $this->getContainer()->query(Extension::class);
		});
	}

	public function getSyncService() {
		return $this->getContainer()->query(SyncService::class);
	}

}
