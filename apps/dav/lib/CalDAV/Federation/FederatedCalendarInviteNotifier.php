<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class FederatedCalendarInviteNotifier implements INotifier {
	public function __construct(
		private readonly IFactory $l10nFactory,
		private readonly IURLGenerator $url,
	) {
	}

	#[\Override]
	public function getID(): string {
		return Application::APP_ID;
	}

	#[\Override]
	public function getName(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('Calendar federation');
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID
			|| $notification->getObjectType() !== FederatedCalendarInvitationService::NOTIFICATION_OBJECT_TYPE) {
			throw new UnknownNotificationException();
		}

		$l = $this->l10nFactory->get(Application::APP_ID, $languageCode);

		return match ($notification->getSubject()) {
			FederatedCalendarInvitationService::NOTIFICATION_SUBJECT_NEW_SHARE
				=> $this->parseNewShare($notification, $l),
			default => throw new UnknownNotificationException(),
		};
	}

	private function parseNewShare(INotification $notification, IL10N $l): INotification {
		$params = $notification->getSubjectParameters();
		$sharedBy = $params['sharedBy'] ?? '';
		$sharedByDisplayName = $params['sharedByDisplayName'] ?? '';
		$calendarName = $params['calendarName'] ?? '';

		$notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'places/calendar.svg')));
		$notification->setRichSubject(
			$l->t('{user} has shared the calendar {calendar} with you'),
			[
				'calendar' => [
					'type' => 'calendar',
					'id' => $notification->getObjectId(),
					'name' => $calendarName,
				],
				'user' => $this->createRemoteUser($sharedBy, $sharedByDisplayName),
			],
		);

		foreach ($notification->getActions() as $action) {
			switch ($action->getLabel()) {
				case 'accept':
					$action->setParsedLabel($l->t('Accept'))
						->setPrimary(true);
					break;
				case 'decline':
					$action->setParsedLabel($l->t('Decline'));
					break;
			}

			$notification->addParsedAction($action);
		}

		return $notification;
	}

	private function createRemoteUser(string $cloudId, string $displayName): array {
		$user = $cloudId;
		$server = '';
		$atPos = strrpos($cloudId, '@');
		if ($atPos !== false) {
			$user = substr($cloudId, 0, $atPos);
			$server = substr($cloudId, $atPos + 1);
		}

		return [
			'type' => 'user',
			'id' => $user,
			'name' => $displayName !== '' ? $displayName : $user,
			'server' => $server,
		];
	}
}
