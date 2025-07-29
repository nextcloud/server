<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Comments\Notification;

use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class Notifier implements INotifier {
	public function __construct(
		protected IFactory $l10nFactory,
		protected IRootFolder $rootFolder,
		protected ICommentsManager $commentsManager,
		protected IURLGenerator $url,
		protected IUserManager $userManager,
	) {
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return 'comments';
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->l10nFactory->get('comments')->t('Comments');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws UnknownNotificationException When the notification was not prepared by a notifier
	 * @throws AlreadyProcessedException When the notification is not needed anymore and should be deleted
	 * @since 9.0.0
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'comments') {
			throw new UnknownNotificationException();
		}
		try {
			$comment = $this->commentsManager->get($notification->getObjectId());
		} catch (NotFoundException $e) {
			// needs to be converted to InvalidArgumentException, otherwise none Notifications will be shown at all
			throw new UnknownNotificationException('Comment not found', 0, $e);
		}
		$l = $this->l10nFactory->get('comments', $languageCode);
		$displayName = $comment->getActorId();
		$isDeletedActor = $comment->getActorType() === ICommentsManager::DELETED_USER;
		if ($comment->getActorType() === 'users') {
			$commenter = $this->userManager->getDisplayName($comment->getActorId());
			if ($commenter !== null) {
				$displayName = $commenter;
			}
		}

		switch ($notification->getSubject()) {
			case 'mention':
				$parameters = $notification->getSubjectParameters();
				if ($parameters[0] !== 'files') {
					throw new UnknownNotificationException('Unsupported comment object');
				}
				$userFolder = $this->rootFolder->getUserFolder($notification->getUser());
				$nodes = $userFolder->getById((int)$parameters[1]);
				if (empty($nodes)) {
					throw new AlreadyProcessedException();
				}
				$node = $nodes[0];

				$path = rtrim($node->getPath(), '/');
				if (str_starts_with($path, '/' . $notification->getUser() . '/files/')) {
					// Remove /user/files/...
					$fullPath = $path;
					[,,, $path] = explode('/', $fullPath, 4);
				}
				$subjectParameters = [
					'file' => [
						'type' => 'file',
						'id' => $comment->getObjectId(),
						'name' => $node->getName(),
						'path' => $path,
						'link' => $this->url->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $comment->getObjectId()]),
					],
				];

				if ($isDeletedActor) {
					$subject = $l->t('You were mentioned on "{file}", in a comment by an account that has since been deleted');
				} else {
					$subject = $l->t('{user} mentioned you in a comment on "{file}"');
					$subjectParameters['user'] = [
						'type' => 'user',
						'id' => $comment->getActorId(),
						'name' => $displayName,
					];
				}
				[$message, $messageParameters] = $this->commentToRichMessage($comment);
				$notification->setRichSubject($subject, $subjectParameters)
					->setRichMessage($message, $messageParameters)
					->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/comment.svg')))
					->setLink($this->url->linkToRouteAbsolute(
						'comments.Notifications.view',
						['id' => $comment->getId()])
					);

				return $notification;
				break;

			default:
				throw new UnknownNotificationException('Invalid subject');
		}
	}

	public function commentToRichMessage(IComment $comment): array {
		$message = $comment->getMessage();
		$messageParameters = [];
		$mentionTypeCount = [];
		$mentions = $comment->getMentions();
		foreach ($mentions as $mention) {
			if ($mention['type'] === 'user') {
				$userDisplayName = $this->userManager->getDisplayName($mention['id']);
				if ($userDisplayName === null) {
					continue;
				}
			}
			if (!array_key_exists($mention['type'], $mentionTypeCount)) {
				$mentionTypeCount[$mention['type']] = 0;
			}
			$mentionTypeCount[$mention['type']]++;
			// To keep a limited character set in parameter IDs ([a-zA-Z0-9-])
			// the mention parameter ID does not include the mention ID (which
			// could contain characters like '@' for user IDs) but a one-based
			// index of the mentions of that type.
			$mentionParameterId = 'mention-' . $mention['type'] . $mentionTypeCount[$mention['type']];
			$message = str_replace('@"' . $mention['id'] . '"', '{' . $mentionParameterId . '}', $message);
			if (!str_contains($mention['id'], ' ') && !str_starts_with($mention['id'], 'guest/')) {
				$message = str_replace('@' . $mention['id'], '{' . $mentionParameterId . '}', $message);
			}

			try {
				$displayName = $this->commentsManager->resolveDisplayName($mention['type'], $mention['id']);
			} catch (\OutOfBoundsException $e) {
				// There is no registered display name resolver for the mention
				// type, so the client decides what to display.
				$displayName = '';
			}
			$messageParameters[$mentionParameterId] = [
				'type' => $mention['type'],
				'id' => $mention['id'],
				'name' => $displayName
			];
		}
		return [$message, $messageParameters];
	}
}
