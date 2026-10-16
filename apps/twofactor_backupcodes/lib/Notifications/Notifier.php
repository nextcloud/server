<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Notifications;

use OCA\TwoFactorBackupCodes\Provider\BackupCodesProvider;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class Notifier implements INotifier {

	public function __construct(
		private IFactory $factory,
		private IURLGenerator $url,
		private BackupCodesProvider $provider,
		private IUserManager $userManager,
	) {
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return 'twofactor_backupcodes';
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->factory->get('twofactor_backupcodes')->t('Second-factor backup codes');
	}

	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'twofactor_backupcodes') {
			// Not my app => throw
			throw new UnknownNotificationException();
		}

		// Read the language from the notification
		$l = $this->factory->get('twofactor_backupcodes', $languageCode);

		switch ($notification->getSubject()) {
			case 'create_backupcodes':
				$user = $this->userManager->get($notification->getUser());
				if ($user === null) {
					throw new UnknownNotificationException();
				}
				if (!$this->provider->isActive($user)) {
					// No other 2FA available, so this provider is also not working anymore.
					// Dismiss the notification.
					throw new AlreadyProcessedException();
				}

				$notification->setParsedSubject(
					$l->t('Generate backup codes')
				)->setParsedMessage(
					$l->t('You enabled two-factor authentication but did not generate backup codes yet. They are needed to restore access to your account in case you lose your second factor.')
				);

				$notification->setLink($this->url->linkToRouteAbsolute('settings.PersonalSettings.index', ['section' => 'security']));

				$notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/password.svg')));

				return $notification;

			default:
				// Unknown subject => Unknown notification => throw
				throw new UnknownNotificationException();
		}
	}
}
