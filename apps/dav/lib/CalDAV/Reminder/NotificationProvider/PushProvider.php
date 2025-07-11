<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Reminder\NotificationProvider;

use OCA\DAV\AppInfo\Application;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory as L10NFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Property;

/**
 * Class PushProvider
 *
 * @package OCA\DAV\CalDAV\Reminder\NotificationProvider
 */
class PushProvider extends AbstractProvider {

	/** @var string */
	public const NOTIFICATION_TYPE = 'DISPLAY';

	public function __construct(
		IConfig $config,
		private IManager $manager,
		LoggerInterface $logger,
		L10NFactory $l10nFactory,
		IURLGenerator $urlGenerator,
		private ITimeFactory $timeFactory,
	) {
		parent::__construct($logger, $l10nFactory, $urlGenerator, $config);
	}

	/**
	 * Send push notification to all users.
	 *
	 * @param VEvent $vevent
	 * @param string|null $calendarDisplayName
	 * @param string[] $principalEmailAddresses
	 * @param IUser[] $users
	 * @throws \Exception
	 */
	public function send(VEvent $vevent,
		?string $calendarDisplayName,
		array $principalEmailAddresses,
		array $users = []):void {
		if ($this->config->getAppValue('dav', 'sendEventRemindersPush', 'yes') !== 'yes') {
			return;
		}

		$eventDetails = $this->extractEventDetails($vevent);
		$eventUUID = (string)$vevent->UID;
		if (!$eventUUID) {
			return;
		};
		$eventUUIDHash = hash('sha256', $eventUUID, false);

		foreach ($users as $user) {
			$eventDetails['calendar_displayname'] = $calendarDisplayName ?? $this->getCalendarDisplayNameFallback($this->l10nFactory->getUserLanguage($user));

			/** @var INotification $notification */
			$notification = $this->manager->createNotification();
			$notification->setApp(Application::APP_ID)
				->setUser($user->getUID())
				->setDateTime($this->timeFactory->getDateTime())
				->setObject(Application::APP_ID, $eventUUIDHash)
				->setSubject('calendar_reminder', [
					'title' => $eventDetails['title'],
					'start_atom' => $eventDetails['start_atom']
				])
				->setMessage('calendar_reminder', $eventDetails);

			$this->manager->notify($notification);
		}
	}

	/**
	 * @throws \Exception
	 */
	protected function extractEventDetails(VEvent $vevent):array {
		/** @var Property\ICalendar\DateTime $start */
		$start = $vevent->DTSTART;
		$end = $this->getDTEndFromEvent($vevent);

		return [
			'title' => isset($vevent->SUMMARY)
				? ((string)$vevent->SUMMARY)
				: null,
			'description' => isset($vevent->DESCRIPTION)
				? ((string)$vevent->DESCRIPTION)
				: null,
			'location' => isset($vevent->LOCATION)
				? ((string)$vevent->LOCATION)
				: null,
			'all_day' => $start instanceof Property\ICalendar\Date,
			'start_atom' => $start->getDateTime()->format(\DateTimeInterface::ATOM),
			'start_is_floating' => $start->isFloating(),
			'start_timezone' => $start->getDateTime()->getTimezone()->getName(),
			'end_atom' => $end->getDateTime()->format(\DateTimeInterface::ATOM),
			'end_is_floating' => $end->isFloating(),
			'end_timezone' => $end->getDateTime()->getTimezone()->getName(),
		];
	}
}
