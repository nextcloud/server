<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tobia De Koninck <tobia@ledfan.be>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\AppInfo;

use Exception;
use OCA\DAV\BackgroundJob\UpdateCalendarResourcesRoomsBackgroundJob;
use OCA\DAV\CalDAV\Activity\Backend;
use OCA\DAV\CalDAV\CalendarManager;
use OCA\DAV\CalDAV\CalendarProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\AudioProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\EmailProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\PushProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProviderManager;
use OCA\DAV\CalDAV\Reminder\Notifier;

use OCA\DAV\Capabilities;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\ContactsManager;
use OCA\DAV\CardDAV\PhotoCache;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\Events\AddressBookCreatedEvent;
use OCA\DAV\Events\AddressBookDeletedEvent;
use OCA\DAV\Events\AddressBookShareUpdatedEvent;
use OCA\DAV\Events\AddressBookUpdatedEvent;
use OCA\DAV\Events\CalendarCreatedEvent;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Events\CalendarMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectDeletedEvent;
use OCA\DAV\Events\CalendarObjectMovedEvent;
use OCA\DAV\Events\CalendarObjectMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectRestoredEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\CalendarPublishedEvent;
use OCA\DAV\Events\CalendarRestoredEvent;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCA\DAV\Events\CalendarUnpublishedEvent;
use OCA\DAV\Events\CalendarUpdatedEvent;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCA\DAV\Events\SubscriptionCreatedEvent;
use OCA\DAV\Events\SubscriptionDeletedEvent;
use OCP\Federation\Events\TrustedServerRemovedEvent;
use OCA\DAV\HookManager;
use OCA\DAV\Listener\ActivityUpdaterListener;
use OCA\DAV\Listener\AddressbookListener;
use OCA\DAV\Listener\BirthdayListener;
use OCA\DAV\Listener\CalendarContactInteractionListener;
use OCA\DAV\Listener\CalendarDeletionDefaultUpdaterListener;
use OCA\DAV\Listener\CalendarObjectReminderUpdaterListener;
use OCA\DAV\Listener\CalendarPublicationListener;
use OCA\DAV\Listener\CalendarShareUpdateListener;
use OCA\DAV\Listener\CardListener;
use OCA\DAV\Listener\ClearPhotoCacheListener;
use OCA\DAV\Listener\SubscriptionListener;
use OCA\DAV\Listener\TrustedServerRemovedListener;
use OCA\DAV\Listener\UserPreferenceListener;
use OCA\DAV\Search\ContactsSearchProvider;
use OCA\DAV\Search\EventsSearchProvider;
use OCA\DAV\Search\TasksSearchProvider;
use OCA\DAV\UserMigration\CalendarMigrator;
use OCA\DAV\UserMigration\ContactsMigrator;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\IAppContainer;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\Contacts\IManager as IContactsManager;
use OCP\IServerContainer;
use OCP\IUser;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Throwable;
use function is_null;

class Application extends App implements IBootstrap {
	public const APP_ID = 'dav';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerServiceAlias('CardDAVSyncService', SyncService::class);
		$context->registerService(PhotoCache::class, function (ContainerInterface $c) {
			/** @var IServerContainer $server */
			$server = $c->get(IServerContainer::class);

			return new PhotoCache(
				$server->getAppDataDir('dav-photocache'),
				$c->get(LoggerInterface::class)
			);
		});

		/*
		 * Register capabilities
		 */
		$context->registerCapability(Capabilities::class);

		/*
		 * Register Search Providers
		 */
		$context->registerSearchProvider(ContactsSearchProvider::class);
		$context->registerSearchProvider(EventsSearchProvider::class);
		$context->registerSearchProvider(TasksSearchProvider::class);

		/**
		 * Register event listeners
		 */
		$context->registerEventListener(CalendarCreatedEvent::class, ActivityUpdaterListener::class);
		$context->registerEventListener(CalendarDeletedEvent::class, ActivityUpdaterListener::class);
		$context->registerEventListener(CalendarDeletedEvent::class, CalendarObjectReminderUpdaterListener::class);
		$context->registerEventListener(CalendarDeletedEvent::class, CalendarDeletionDefaultUpdaterListener::class);
		$context->registerEventListener(CalendarMovedToTrashEvent::class, ActivityUpdaterListener::class);
		$context->registerEventListener(CalendarMovedToTrashEvent::class, CalendarObjectReminderUpdaterListener::class);
		$context->registerEventListener(CalendarUpdatedEvent::class, ActivityUpdaterListener::class);
		$context->registerEventListener(CalendarRestoredEvent::class, ActivityUpdaterListener::class);
		$context->registerEventListener(CalendarRestoredEvent::class, CalendarObjectReminderUpdaterListener::class);
		$context->registerEventListener(CalendarObjectCreatedEvent::class, ActivityUpdaterListener::class);
		$context->registerEventListener(CalendarObjectCreatedEvent::class, CalendarContactInteractionListener::class);
		$context->registerEventListener(CalendarObjectCreatedEvent::class, CalendarObjectReminderUpdaterListener::class);
		$context->registerEventListener(CalendarObjectUpdatedEvent::class, ActivityUpdaterListener::class);
		$context->registerEventListener(CalendarObjectUpdatedEvent::class, CalendarContactInteractionListener::class);
		$context->registerEventListener(CalendarObjectUpdatedEvent::class, CalendarObjectReminderUpdaterListener::class);
		$context->registerEventListener(CalendarObjectDeletedEvent::class, ActivityUpdaterListener::class);
		$context->registerEventListener(CalendarObjectDeletedEvent::class, CalendarObjectReminderUpdaterListener::class);
		$context->registerEventListener(CalendarObjectMovedEvent::class, ActivityUpdaterListener::class);
		$context->registerEventListener(CalendarObjectMovedEvent::class, CalendarObjectReminderUpdaterListener::class);
		$context->registerEventListener(CalendarObjectMovedToTrashEvent::class, ActivityUpdaterListener::class);
		$context->registerEventListener(CalendarObjectMovedToTrashEvent::class, CalendarObjectReminderUpdaterListener::class);
		$context->registerEventListener(CalendarObjectRestoredEvent::class, ActivityUpdaterListener::class);
		$context->registerEventListener(CalendarObjectRestoredEvent::class, CalendarObjectReminderUpdaterListener::class);
		$context->registerEventListener(CalendarShareUpdatedEvent::class, CalendarContactInteractionListener::class);
		$context->registerEventListener(CalendarPublishedEvent::class, CalendarPublicationListener::class);
		$context->registerEventListener(CalendarUnpublishedEvent::class, CalendarPublicationListener::class);
		$context->registerEventListener(CalendarShareUpdatedEvent::class, CalendarShareUpdateListener::class);

		$context->registerEventListener(SubscriptionCreatedEvent::class, SubscriptionListener::class);
		$context->registerEventListener(SubscriptionDeletedEvent::class, SubscriptionListener::class);


		$context->registerEventListener(AddressBookCreatedEvent::class, AddressbookListener::class);
		$context->registerEventListener(AddressBookDeletedEvent::class, AddressbookListener::class);
		$context->registerEventListener(AddressBookUpdatedEvent::class, AddressbookListener::class);
		$context->registerEventListener(AddressBookShareUpdatedEvent::class, AddressbookListener::class);
		$context->registerEventListener(CardCreatedEvent::class, CardListener::class);
		$context->registerEventListener(CardDeletedEvent::class, CardListener::class);
		$context->registerEventListener(CardUpdatedEvent::class, CardListener::class);
		$context->registerEventListener(CardCreatedEvent::class, BirthdayListener::class);
		$context->registerEventListener(CardDeletedEvent::class, BirthdayListener::class);
		$context->registerEventListener(CardUpdatedEvent::class, BirthdayListener::class);
		$context->registerEventListener(CardDeletedEvent::class, ClearPhotoCacheListener::class);
		$context->registerEventListener(CardUpdatedEvent::class, ClearPhotoCacheListener::class);
		$context->registerEventListener(TrustedServerRemovedEvent::class, TrustedServerRemovedListener::class);

		$context->registerEventListener(BeforePreferenceDeletedEvent::class, UserPreferenceListener::class);
		$context->registerEventListener(BeforePreferenceSetEvent::class, UserPreferenceListener::class);

		$context->registerNotifierService(Notifier::class);

		$context->registerCalendarProvider(CalendarProvider::class);

		$context->registerUserMigrator(CalendarMigrator::class);
		$context->registerUserMigrator(ContactsMigrator::class);
	}

	public function boot(IBootContext $context): void {
		// Load all dav apps
		\OC_App::loadApps(['dav']);

		$context->injectFn([$this, 'registerHooks']);
		$context->injectFn([$this, 'registerContactsManager']);
		$context->injectFn([$this, 'registerCalendarManager']);
		$context->injectFn([$this, 'registerCalendarReminders']);
	}

	public function registerHooks(HookManager $hm,
								   EventDispatcherInterface $dispatcher,
								   IAppContainer $container,
								   IServerContainer $serverContainer) {
		$hm->setup();

		// first time login event setup
		$dispatcher->addListener(IUser::class . '::firstLogin', function ($event) use ($hm) {
			if ($event instanceof GenericEvent) {
				$hm->firstLogin($event->getSubject());
			}
		});

		$dispatcher->addListener('OC\AccountManager::userUpdated', function (GenericEvent $event) use ($container) {
			$user = $event->getSubject();
			/** @var SyncService $syncService */
			$syncService = $container->query(SyncService::class);
			$syncService->updateUser($user);
		});


		$dispatcher->addListener('\OCA\DAV\CalDAV\CalDavBackend::updateShares', function (GenericEvent $event) use ($container) {
			/** @var Backend $backend */
			$backend = $container->query(Backend::class);
			$backend->onCalendarUpdateShares(
				$event->getArgument('calendarData'),
				$event->getArgument('shares'),
				$event->getArgument('add'),
				$event->getArgument('remove')
			);

			// Here we should recalculate if reminders should be sent to new or old sharees
		});

		$eventHandler = function () use ($container, $serverContainer): void {
			try {
				/** @var UpdateCalendarResourcesRoomsBackgroundJob $job */
				$job = $container->query(UpdateCalendarResourcesRoomsBackgroundJob::class);
				$job->run([]);
				$serverContainer->getJobList()->setLastRun($job);
			} catch (Exception $ex) {
				$serverContainer->get(LoggerInterface::class)->error($ex->getMessage(), ['exception' => $ex]);
			}
		};

		$dispatcher->addListener('\OCP\Calendar\Resource\ForceRefreshEvent', $eventHandler);
		$dispatcher->addListener('\OCP\Calendar\Room\ForceRefreshEvent', $eventHandler);
	}

	public function registerContactsManager(IContactsManager $cm, IAppContainer $container): void {
		$cm->register(function () use ($container, $cm): void {
			$user = \OC::$server->getUserSession()->getUser();
			if (!is_null($user)) {
				$this->setupContactsProvider($cm, $container, $user->getUID());
			} else {
				$this->setupSystemContactsProvider($cm, $container);
			}
		});
	}

	private function setupContactsProvider(IContactsManager $contactsManager,
										   IAppContainer $container,
										   string $userID): void {
		/** @var ContactsManager $cm */
		$cm = $container->query(ContactsManager::class);
		$urlGenerator = $container->getServer()->getURLGenerator();
		$cm->setupContactsProvider($contactsManager, $userID, $urlGenerator);
	}

	private function setupSystemContactsProvider(IContactsManager $contactsManager,
												 IAppContainer $container): void {
		/** @var ContactsManager $cm */
		$cm = $container->query(ContactsManager::class);
		$urlGenerator = $container->getServer()->getURLGenerator();
		$cm->setupSystemContactsProvider($contactsManager, $urlGenerator);
	}

	public function registerCalendarManager(ICalendarManager $calendarManager,
											 IAppContainer $container): void {
		$calendarManager->register(function () use ($container, $calendarManager) {
			$user = \OC::$server->getUserSession()->getUser();
			if ($user !== null) {
				$this->setupCalendarProvider($calendarManager, $container, $user->getUID());
			}
		});
	}

	private function setupCalendarProvider(ICalendarManager $calendarManager,
										   IAppContainer $container,
										   $userId) {
		$cm = $container->query(CalendarManager::class);
		$cm->setupCalendarProvider($calendarManager, $userId);
	}

	public function registerCalendarReminders(NotificationProviderManager $manager,
											   LoggerInterface $logger): void {
		try {
			$manager->registerProvider(AudioProvider::class);
			$manager->registerProvider(EmailProvider::class);
			$manager->registerProvider(PushProvider::class);
		} catch (Throwable $ex) {
			$logger->error($ex->getMessage(), ['exception' => $ex]);
		}
	}
}
