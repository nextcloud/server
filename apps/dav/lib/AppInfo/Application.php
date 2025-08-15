<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\AppInfo;

use OCA\DAV\CalDAV\AppCalendar\AppCalendarPlugin;
use OCA\DAV\CalDAV\CachedSubscriptionProvider;
use OCA\DAV\CalDAV\CalendarManager;
use OCA\DAV\CalDAV\CalendarProvider;
use OCA\DAV\CalDAV\Federation\CalendarFederationProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\AudioProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\EmailProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\PushProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProviderManager;
use OCA\DAV\CalDAV\Reminder\Notifier;
use OCA\DAV\Capabilities;
use OCA\DAV\CardDAV\ContactsManager;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\Events\AddressBookCreatedEvent;
use OCA\DAV\Events\AddressBookDeletedEvent;
use OCA\DAV\Events\AddressBookShareUpdatedEvent;
use OCA\DAV\Events\AddressBookUpdatedEvent;
use OCA\DAV\Events\CalendarCreatedEvent;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Events\CalendarMovedToTrashEvent;
use OCA\DAV\Events\CalendarPublishedEvent;
use OCA\DAV\Events\CalendarRestoredEvent;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCA\DAV\Events\CalendarUnpublishedEvent;
use OCA\DAV\Events\CalendarUpdatedEvent;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCA\DAV\Events\SabrePluginAuthInitEvent;
use OCA\DAV\Events\SubscriptionCreatedEvent;
use OCA\DAV\Events\SubscriptionDeletedEvent;
use OCA\DAV\Listener\ActivityUpdaterListener;
use OCA\DAV\Listener\AddMissingIndicesListener;
use OCA\DAV\Listener\AddressbookListener;
use OCA\DAV\Listener\BirthdayListener;
use OCA\DAV\Listener\CalendarContactInteractionListener;
use OCA\DAV\Listener\CalendarDeletionDefaultUpdaterListener;
use OCA\DAV\Listener\CalendarFederationNotificationListener;
use OCA\DAV\Listener\CalendarObjectReminderUpdaterListener;
use OCA\DAV\Listener\CalendarPublicationListener;
use OCA\DAV\Listener\CalendarShareUpdateListener;
use OCA\DAV\Listener\CardListener;
use OCA\DAV\Listener\ClearPhotoCacheListener;
use OCA\DAV\Listener\DavAdminSettingsListener;
use OCA\DAV\Listener\OutOfOfficeListener;
use OCA\DAV\Listener\SabrePluginAuthInitListener;
use OCA\DAV\Listener\SubscriptionListener;
use OCA\DAV\Listener\TrustedServerRemovedListener;
use OCA\DAV\Listener\UserEventsListener;
use OCA\DAV\Listener\UserPreferenceListener;
use OCA\DAV\Search\ContactsSearchProvider;
use OCA\DAV\Search\EventsSearchProvider;
use OCA\DAV\Search\TasksSearchProvider;
use OCA\DAV\Settings\Admin\SystemAddressBookSettings;
use OCA\DAV\SetupChecks\NeedsSystemAddressBookSync;
use OCA\DAV\SetupChecks\WebdavEndpoint;
use OCA\DAV\UserMigration\CalendarMigrator;
use OCA\DAV\UserMigration\ContactsMigrator;
use OCP\Accounts\UserUpdatedEvent;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\IAppContainer;
use OCP\Calendar\Events\CalendarObjectCreatedEvent;
use OCP\Calendar\Events\CalendarObjectDeletedEvent;
use OCP\Calendar\Events\CalendarObjectMovedEvent;
use OCP\Calendar\Events\CalendarObjectMovedToTrashEvent;
use OCP\Calendar\Events\CalendarObjectRestoredEvent;
use OCP\Calendar\Events\CalendarObjectUpdatedEvent;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\Contacts\IManager as IContactsManager;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\Federation\Events\TrustedServerRemovedEvent;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\Settings\Events\DeclarativeSettingsGetValueEvent;
use OCP\Settings\Events\DeclarativeSettingsSetValueEvent;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\BeforeUserIdUnassignedEvent;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeClearedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserFirstTimeLoggedInEvent;
use OCP\User\Events\UserIdAssignedEvent;
use OCP\User\Events\UserIdUnassignedEvent;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use function is_null;

class Application extends App implements IBootstrap {
	public const APP_ID = 'dav';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerServiceAlias('CardDAVSyncService', SyncService::class);
		$context->registerService(AppCalendarPlugin::class, function (ContainerInterface $c) {
			return new AppCalendarPlugin(
				$c->get(ICalendarManager::class),
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
		$context->registerEventListener(AddMissingIndicesEvent::class, AddMissingIndicesListener::class);

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

		$context->registerEventListener(OutOfOfficeChangedEvent::class, OutOfOfficeListener::class);
		$context->registerEventListener(OutOfOfficeClearedEvent::class, OutOfOfficeListener::class);
		$context->registerEventListener(OutOfOfficeScheduledEvent::class, OutOfOfficeListener::class);

		$context->registerEventListener(UserFirstTimeLoggedInEvent::class, UserEventsListener::class);
		$context->registerEventListener(UserIdAssignedEvent::class, UserEventsListener::class);
		$context->registerEventListener(BeforeUserIdUnassignedEvent::class, UserEventsListener::class);
		$context->registerEventListener(UserIdUnassignedEvent::class, UserEventsListener::class);
		$context->registerEventListener(BeforeUserDeletedEvent::class, UserEventsListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserEventsListener::class);
		$context->registerEventListener(UserCreatedEvent::class, UserEventsListener::class);
		$context->registerEventListener(UserChangedEvent::class, UserEventsListener::class);
		$context->registerEventListener(UserUpdatedEvent::class, UserEventsListener::class);

		$context->registerEventListener(SabrePluginAuthInitEvent::class, SabrePluginAuthInitListener::class);

		$context->registerEventListener(CalendarObjectCreatedEvent::class, CalendarFederationNotificationListener::class);
		$context->registerEventListener(CalendarObjectUpdatedEvent::class, CalendarFederationNotificationListener::class);
		$context->registerEventListener(CalendarObjectDeletedEvent::class, CalendarFederationNotificationListener::class);

		$context->registerNotifierService(Notifier::class);

		$context->registerCalendarProvider(CalendarProvider::class);
		$context->registerCalendarProvider(CachedSubscriptionProvider::class);

		$context->registerUserMigrator(CalendarMigrator::class);
		$context->registerUserMigrator(ContactsMigrator::class);

		$context->registerSetupCheck(NeedsSystemAddressBookSync::class);
		$context->registerSetupCheck(WebdavEndpoint::class);

		// register admin settings form and listener(s)
		$context->registerDeclarativeSettings(SystemAddressBookSettings::class);
		$context->registerEventListener(DeclarativeSettingsGetValueEvent::class, DavAdminSettingsListener::class);
		$context->registerEventListener(DeclarativeSettingsSetValueEvent::class, DavAdminSettingsListener::class);
	}

	public function boot(IBootContext $context): void {
		// Load all dav apps
		$context->getServerContainer()->get(IAppManager::class)->loadApps(['dav']);

		$context->injectFn($this->registerContactsManager(...));
		$context->injectFn($this->registerCalendarManager(...));
		$context->injectFn($this->registerCalendarReminders(...));
		$context->injectFn($this->registerCloudFederationProvider(...));
	}

	public function registerContactsManager(IContactsManager $cm, IAppContainer $container): void {
		$cm->register(function () use ($container, $cm): void {
			$user = Server::get(IUserSession::class)->getUser();
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

	private function setupSystemContactsProvider(IContactsManager $contactsManager, IAppContainer $container): void {
		/** @var ContactsManager $cm */
		$cm = $container->query(ContactsManager::class);
		$urlGenerator = $container->getServer()->getURLGenerator();
		$cm->setupSystemContactsProvider($contactsManager, null, $urlGenerator);
	}

	public function registerCalendarManager(ICalendarManager $calendarManager,
		IAppContainer $container): void {
		$calendarManager->register(function () use ($container, $calendarManager): void {
			$user = Server::get(IUserSession::class)->getUser();
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

	public function registerCloudFederationProvider(
		ICloudFederationProviderManager $manager,
	): void {
		$manager->addCloudFederationProvider(
			CalendarFederationProvider::PROVIDER_ID,
			'Calendar Federation',
			static fn () => Server::get(CalendarFederationProvider::class),
		);
	}
}
