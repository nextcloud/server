<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\FederatedFileSharing;


use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {
	/** @var \OCP\L10N\IFactory */
	protected $factory;

	/**
	 * @param \OCP\L10N\IFactory $factory
	 */
	public function __construct(\OCP\L10N\IFactory $factory) {
		$this->factory = $factory;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== 'files_sharing') {
			// Not my app => throw
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->factory->get('files_sharing', $languageCode);

		switch ($notification->getSubject()) {
			// Deal with known subjects
			case 'remote_share':
				$params = $notification->getSubjectParameters();
				if ($params[0] !== $params[1] && $params[1] !== null) {
					$notification->setParsedSubject(
						(string) $l->t('You received "/%3$s" as a remote share from %1$s (on behalf of %2$s)', $params)
					);
				} else {
					$notification->setParsedSubject(
						(string)$l->t('You received "/%3$s" as a remote share from %1$s', $params)
					);
				}

				// Deal with the actions for a known subject
				foreach ($notification->getActions() as $action) {
					switch ($action->getLabel()) {
						case 'accept':
							$action->setParsedLabel(
								(string) $l->t('Accept')
							)
							->setPrimary(true);
							break;

						case 'decline':
							$action->setParsedLabel(
								(string) $l->t('Decline')
							);
							break;
					}

					$notification->addParsedAction($action);
				}
				return $notification;

			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
	}
}
