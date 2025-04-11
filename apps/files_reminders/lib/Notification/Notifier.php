<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\Notification;

use OCA\FilesReminders\AppInfo\Application;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\IAction;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class Notifier implements INotifier {
	public function __construct(
		protected IFactory $l10nFactory,
		protected IURLGenerator $urlGenerator,
		protected IRootFolder $root,
	) {
	}

	public function getID(): string {
		return Application::APP_ID;
	}

	public function getName(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('File reminders');
	}

	/**
	 * @throws UnknownNotificationException
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get(Application::APP_ID, $languageCode);

		if ($notification->getApp() !== Application::APP_ID) {
			throw new UnknownNotificationException();
		}

		switch ($notification->getSubject()) {
			case 'reminder-due':
				$params = $notification->getSubjectParameters();
				$fileId = $params['fileId'];

				$node = $this->root->getUserFolder($notification->getUser())->getFirstNodeById($fileId);
				if ($node === null) {
					throw new AlreadyProcessedException();
				}

				$path = rtrim($node->getPath(), '/');
				if (strpos($path, '/' . $notification->getUser() . '/files/') === 0) {
					// Remove /user/files/...
					$fullPath = $path;
					[,,, $path] = explode('/', $fullPath, 4);
				}

				$link = $this->urlGenerator->linkToRouteAbsolute(
					'files.viewcontroller.showFile',
					['fileid' => $node->getId()],
				);

				// TRANSLATORS The name placeholder is for a file or folder name
				$subject = $l->t('Reminder for {name}');
				$notification
					->setRichSubject(
						$subject,
						[
							'name' => [
								'type' => 'highlight',
								'id' => (string)$node->getId(),
								'name' => $node->getName(),
							],
						],
					)
					->setLink($link);

				$label = match ($node->getType()) {
					FileInfo::TYPE_FILE => $l->t('View file'),
					FileInfo::TYPE_FOLDER => $l->t('View folder'),
				};

				$this->addActionButton($notification, $label);
				break;
			default:
				throw new UnknownNotificationException();
		}

		return $notification;
	}

	protected function addActionButton(INotification $notification, string $label): void {
		$action = $notification->createAction();

		$action->setLabel($label)
			->setParsedLabel($label)
			->setLink($notification->getLink(), IAction::TYPE_WEB)
			->setPrimary(true);

		$notification->addParsedAction($action);
	}
}
