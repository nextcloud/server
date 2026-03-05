<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Notifications;

use OCP\L10N\IFactory as IL10nFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class Notifier implements INotifier {
	public function __construct(
		private IL10nFactory $factory,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'auth') {
			// Not my app => throw
			throw new UnknownNotificationException();
		}

		// Read the language from the notification
		$l = $this->factory->get('lib', $languageCode);

		switch ($notification->getSubject()) {
			case 'remote_wipe_start':
				$notification->setParsedSubject(
					$l->t('Remote wipe started')
				)->setParsedMessage(
					$l->t('A remote wipe was started on device %s', $notification->getSubjectParameters())
				);

				return $notification;
			case 'remote_wipe_finish':
				$notification->setParsedSubject(
					$l->t('Remote wipe finished')
				)->setParsedMessage(
					$l->t('The remote wipe on %s has finished', $notification->getSubjectParameters())
				);

				return $notification;
			default:
				// Unknown subject => Unknown notification => throw
				throw new UnknownNotificationException();
		}
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return 'auth';
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->factory->get('lib')->t('Authentication');
	}
}
