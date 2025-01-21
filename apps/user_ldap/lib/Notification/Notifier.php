<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Notification;

use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class Notifier implements INotifier {

	/**
	 * @param IFactory $l10nFactory
	 */
	public function __construct(
		protected IFactory $l10nFactory,
	) {
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return 'user_ldap';
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->l10nFactory->get('user_ldap')->t('LDAP User backend');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws UnknownNotificationException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'user_ldap') {
			// Not my app => throw
			throw new UnknownNotificationException();
		}

		// Read the language from the notification
		$l = $this->l10nFactory->get('user_ldap', $languageCode);

		switch ($notification->getSubject()) {
			// Deal with known subjects
			case 'pwd_exp_warn_days':
				$params = $notification->getSubjectParameters();
				$days = (int)$params[0];
				if ($days === 2) {
					$notification->setParsedSubject($l->t('Your password will expire tomorrow.'));
				} elseif ($days === 1) {
					$notification->setParsedSubject($l->t('Your password will expire today.'));
				} else {
					$notification->setParsedSubject($l->n(
						'Your password will expire within %n day.',
						'Your password will expire within %n days.',
						$days
					));
				}
				return $notification;

			default:
				// Unknown subject => Unknown notification => throw
				throw new UnknownNotificationException();
		}
	}
}
