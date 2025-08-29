<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CardDAV\Notification;

use InvalidArgumentException;
use OCA\DAV\AppInfo\Application;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class Notifier implements INotifier {

	public function __construct(
		protected IFactory $l10nFactory,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getID(): string {
		return Application::APP_ID;
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('Contacts');
	}

	/**
	 * @inheritDoc
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID) {
			throw new InvalidArgumentException();
		}

		$l = $this->l10nFactory->get(Application::APP_ID, $languageCode);

		return match ($notification->getSubject()) {
			'SystemAddressBookDisabled' => $this->parseSystemAddressBookDisabled($notification, $l),
			default => throw new UnknownNotificationException()
		};
	}

	/**
	 * Generates a notification for the system address book being disabled.
	 */
	protected function parseSystemAddressBookDisabled(INotification $notification, IL10N $l): INotification {
		$command = 'occ config:app:set dav system_addressbook_exposed --value="yes"';
		$notification->setParsedSubject(
			$l->t('System address book disabled')
		)->setParsedMessage(
			$l->t('The system contacts address book has been automatically disabled during upgrade. This means that the address book will no longer be available to users in the contacts app or other clients. The system contacts address book was disabled because the amount of contacts in the address book exceeded the maximum recommended number of contacts. This limit is set to prevent performance issues. You can re-enable the system address book with the following command {command}', ['command' => $command])
		);
		return $notification;
	}

}
