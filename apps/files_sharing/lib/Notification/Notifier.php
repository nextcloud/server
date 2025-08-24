<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Notification;

use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;

class Notifier implements INotifier {
	public const INCOMING_USER_SHARE = 'incoming_user_share';
	public const INCOMING_GROUP_SHARE = 'incoming_group_share';

	public function __construct(
		protected IFactory $l10nFactory,
		private IManager $shareManager,
		private IRootFolder $rootFolder,
		protected IGroupManager $groupManager,
		protected IUserManager $userManager,
		protected IURLGenerator $url,
	) {
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return 'files_sharing';
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->l10nFactory->get('files_sharing')->t('File sharing');
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
		if ($notification->getApp() !== 'files_sharing'
			|| ($notification->getSubject() !== 'expiresTomorrow'
				&& $notification->getObjectType() !== 'share')) {
			throw new UnknownNotificationException('Unhandled app or subject');
		}

		$l = $this->l10nFactory->get('files_sharing', $languageCode);
		$attemptId = $notification->getObjectId();

		try {
			$share = $this->shareManager->getShareById($attemptId, $notification->getUser());
		} catch (ShareNotFound $e) {
			throw new AlreadyProcessedException();
		}

		try {
			$share->getNode();
		} catch (NotFoundException $e) {
			// Node is already deleted, so discard the notification
			throw new AlreadyProcessedException();
		}

		if ($notification->getSubject() === 'expiresTomorrow') {
			$notification = $this->parseShareExpiration($share, $notification, $l);
		} else {
			$notification = $this->parseShareInvitation($share, $notification, $l);
		}
		return $notification;
	}

	protected function parseShareExpiration(IShare $share, INotification $notification, IL10N $l): INotification {
		$node = $share->getNode();
		$userFolder = $this->rootFolder->getUserFolder($notification->getUser());
		$path = $userFolder->getRelativePath($node->getPath());

		$notification
			->setParsedSubject($l->t('Share will expire tomorrow'))
			->setRichMessage(
				$l->t('Your share of {node} will expire tomorrow'),
				[
					'node' => [
						'type' => 'file',
						'id' => (string)$node->getId(),
						'name' => $node->getName(),
						'path' => (string)$path,
					],
				]
			);

		return $notification;
	}

	protected function parseShareInvitation(IShare $share, INotification $notification, IL10N $l): INotification {
		if ($share->getShareType() === IShare::TYPE_USER) {
			if ($share->getStatus() !== IShare::STATUS_PENDING) {
				throw new AlreadyProcessedException();
			}
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			if ($share->getStatus() !== IShare::STATUS_PENDING) {
				throw new AlreadyProcessedException();
			}
		} else {
			throw new UnknownNotificationException('Invalid share type');
		}

		switch ($notification->getSubject()) {
			case self::INCOMING_USER_SHARE:
				if ($share->getSharedWith() !== $notification->getUser()) {
					throw new AlreadyProcessedException();
				}

				$sharer = $this->userManager->get($share->getSharedBy());
				if (!$sharer instanceof IUser) {
					throw new \InvalidArgumentException('Temporary failure');
				}

				$subject = $l->t('You received {share} as a share by {user}');
				$subjectParameters = [
					'share' => [
						'type' => 'highlight',
						'id' => $notification->getObjectId(),
						'name' => $share->getTarget(),
					],
					'user' => [
						'type' => 'user',
						'id' => $sharer->getUID(),
						'name' => $sharer->getDisplayName(),
					],
				];
				break;

			case self::INCOMING_GROUP_SHARE:
				$user = $this->userManager->get($notification->getUser());
				if (!$user instanceof IUser) {
					throw new AlreadyProcessedException();
				}

				$group = $this->groupManager->get($share->getSharedWith());
				if ($group === null || !$group->inGroup($user)) {
					throw new AlreadyProcessedException();
				}

				if ($share->getPermissions() === 0) {
					// Already rejected
					throw new AlreadyProcessedException();
				}

				$sharer = $this->userManager->get($share->getSharedBy());
				if (!$sharer instanceof IUser) {
					throw new \InvalidArgumentException('Temporary failure');
				}

				$subject = $l->t('You received {share} to group {group} as a share by {user}');
				$subjectParameters = [
					'share' => [
						'type' => 'highlight',
						'id' => $notification->getObjectId(),
						'name' => $share->getTarget(),
					],
					'group' => [
						'type' => 'user-group',
						'id' => $group->getGID(),
						'name' => $group->getDisplayName(),
					],
					'user' => [
						'type' => 'user',
						'id' => $sharer->getUID(),
						'name' => $sharer->getDisplayName(),
					],
				];
				break;

			default:
				throw new UnknownNotificationException('Invalid subject');
		}

		$notification->setRichSubject($subject, $subjectParameters)
			->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));

		$acceptAction = $notification->createAction();
		$acceptAction->setParsedLabel($l->t('Accept'))
			->setLink($this->url->linkToOCSRouteAbsolute('files_sharing.ShareAPI.acceptShare', ['id' => $share->getId()]), 'POST')
			->setPrimary(true);
		$notification->addParsedAction($acceptAction);

		$rejectAction = $notification->createAction();
		$rejectAction->setParsedLabel($l->t('Decline'))
			->setLink($this->url->linkToOCSRouteAbsolute('files_sharing.ShareAPI.deleteShare', ['id' => $share->getId()]), 'DELETE')
			->setPrimary(false);
		$notification->addParsedAction($rejectAction);

		return $notification;
	}
}
