<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Notification;

use OCA\Files\BackgroundJob\TransferOwnership;
use OCA\Files\Db\TransferOwnershipMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\IAction;
use OCP\Notification\IDismissableNotifier;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class Notifier implements INotifier, IDismissableNotifier {
	public function __construct(
		protected IFactory $l10nFactory,
		protected IURLGenerator $urlGenerator,
		private TransferOwnershipMapper $mapper,
		private IManager $notificationManager,
		private IUserManager $userManager,
		private IJobList $jobList,
		private ITimeFactory $timeFactory,
	) {
	}

	public function getID(): string {
		return 'files';
	}

	public function getName(): string {
		return $this->l10nFactory->get('files')->t('Files');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws UnknownNotificationException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'files') {
			throw new UnknownNotificationException('Unhandled app');
		}

		$imagePath = $this->urlGenerator->imagePath('files', 'folder-move.svg');
		$iconUrl = $this->urlGenerator->getAbsoluteURL($imagePath);
		$notification->setIcon($iconUrl);

		return match($notification->getSubject()) {
			'transferownershipRequest' => $this->handleTransferownershipRequest($notification, $languageCode),
			'transferownershipRequestDenied' => $this->handleTransferOwnershipRequestDenied($notification, $languageCode),
			'transferOwnershipFailedSource' => $this->handleTransferOwnershipFailedSource($notification, $languageCode),
			'transferOwnershipFailedTarget' => $this->handleTransferOwnershipFailedTarget($notification, $languageCode),
			'transferOwnershipDoneSource' => $this->handleTransferOwnershipDoneSource($notification, $languageCode),
			'transferOwnershipDoneTarget' => $this->handleTransferOwnershipDoneTarget($notification, $languageCode),
			default => throw new UnknownNotificationException('Unhandled subject')
		};
	}

	public function handleTransferownershipRequest(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get('files', $languageCode);
		$id = $notification->getObjectId();
		$param = $notification->getSubjectParameters();

		$approveAction = $notification->createAction()
			->setParsedLabel($l->t('Accept'))
			->setPrimary(true)
			->setLink(
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkTo(
						'',
						'ocs/v2.php/apps/files/api/v1/transferownership/' . $id
					)
				),
				IAction::TYPE_POST
			);

		$disapproveAction = $notification->createAction()
			->setParsedLabel($l->t('Reject'))
			->setPrimary(false)
			->setLink(
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkTo(
						'',
						'ocs/v2.php/apps/files/api/v1/transferownership/' . $id
					)
				),
				IAction::TYPE_DELETE
			);

		$sourceUser = $this->getUser($param['sourceUser']);
		$notification->addParsedAction($approveAction)
			->addParsedAction($disapproveAction)
			->setRichSubject(
				$l->t('Incoming ownership transfer from {user}'),
				[
					'user' => [
						'type' => 'user',
						'id' => $sourceUser->getUID(),
						'name' => $sourceUser->getDisplayName(),
					],
				])
			->setRichMessage(
				$l->t("Do you want to accept {path}?\n\nNote: The transfer process after accepting may take up to 1 hour."),
				[
					'path' => [
						'type' => 'highlight',
						'id' => $param['targetUser'] . '::' . $param['nodeName'],
						'name' => $param['nodeName'],
					]
				]);

		return $notification;
	}

	public function handleTransferOwnershipRequestDenied(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get('files', $languageCode);
		$param = $notification->getSubjectParameters();

		$targetUser = $this->getUser($param['targetUser']);
		$notification->setRichSubject($l->t('Ownership transfer denied'))
			->setRichMessage(
				$l->t('Your ownership transfer of {path} was denied by {user}.'),
				[
					'path' => [
						'type' => 'highlight',
						'id' => $param['targetUser'] . '::' . $param['nodeName'],
						'name' => $param['nodeName'],
					],
					'user' => [
						'type' => 'user',
						'id' => $targetUser->getUID(),
						'name' => $targetUser->getDisplayName(),
					],
				]);
		return $notification;
	}

	public function handleTransferOwnershipFailedSource(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get('files', $languageCode);
		$param = $notification->getSubjectParameters();

		$targetUser = $this->getUser($param['targetUser']);
		$notification->setRichSubject($l->t('Ownership transfer failed'))
			->setRichMessage(
				$l->t('Your ownership transfer of {path} to {user} failed.'),
				[
					'path' => [
						'type' => 'highlight',
						'id' => $param['targetUser'] . '::' . $param['nodeName'],
						'name' => $param['nodeName'],
					],
					'user' => [
						'type' => 'user',
						'id' => $targetUser->getUID(),
						'name' => $targetUser->getDisplayName(),
					],
				]);
		return $notification;
	}

	public function handleTransferOwnershipFailedTarget(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get('files', $languageCode);
		$param = $notification->getSubjectParameters();

		$sourceUser = $this->getUser($param['sourceUser']);
		$notification->setRichSubject($l->t('Ownership transfer failed'))
			->setRichMessage(
				$l->t('The ownership transfer of {path} from {user} failed.'),
				[
					'path' => [
						'type' => 'highlight',
						'id' => $param['sourceUser'] . '::' . $param['nodeName'],
						'name' => $param['nodeName'],
					],
					'user' => [
						'type' => 'user',
						'id' => $sourceUser->getUID(),
						'name' => $sourceUser->getDisplayName(),
					],
				]);

		return $notification;
	}

	public function handleTransferOwnershipDoneSource(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get('files', $languageCode);
		$param = $notification->getSubjectParameters();

		$targetUser = $this->getUser($param['targetUser']);
		$notification->setRichSubject($l->t('Ownership transfer done'))
			->setRichMessage(
				$l->t('Your ownership transfer of {path} to {user} has completed.'),
				[
					'path' => [
						'type' => 'highlight',
						'id' => $param['targetUser'] . '::' . $param['nodeName'],
						'name' => $param['nodeName'],
					],
					'user' => [
						'type' => 'user',
						'id' => $targetUser->getUID(),
						'name' => $targetUser->getDisplayName(),
					],
				]);

		return $notification;
	}

	public function handleTransferOwnershipDoneTarget(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get('files', $languageCode);
		$param = $notification->getSubjectParameters();

		$sourceUser = $this->getUser($param['sourceUser']);
		$notification->setRichSubject($l->t('Ownership transfer done'))
			->setRichMessage(
				$l->t('The ownership transfer of {path} from {user} has completed.'),
				[
					'path' => [
						'type' => 'highlight',
						'id' => $param['sourceUser'] . '::' . $param['nodeName'],
						'name' => $param['nodeName'],
					],
					'user' => [
						'type' => 'user',
						'id' => $sourceUser->getUID(),
						'name' => $sourceUser->getDisplayName(),
					],
				]);

		return $notification;
	}

	public function dismissNotification(INotification $notification): void {
		if ($notification->getApp() !== 'files') {
			throw new UnknownNotificationException('Unhandled app');
		}
		if ($notification->getSubject() !== 'transferownershipRequest') {
			throw new UnknownNotificationException('Unhandled notification type');
		}

		// TODO: This should all be moved to a service that also the transferownershipController uses.
		try {
			$transferOwnership = $this->mapper->getById((int)$notification->getObjectId());
		} catch (DoesNotExistException $e) {
			return;
		}

		if ($this->jobList->has(TransferOwnership::class, [
			'id' => $transferOwnership->getId(),
		])) {
			return;
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setUser($transferOwnership->getSourceUser())
			->setApp('files')
			->setDateTime($this->timeFactory->getDateTime())
			->setSubject('transferownershipRequestDenied', [
				'sourceUser' => $transferOwnership->getSourceUser(),
				'targetUser' => $transferOwnership->getTargetUser(),
				'nodeName' => $transferOwnership->getNodeName()
			])
			->setObject('transfer', (string)$transferOwnership->getId());
		$this->notificationManager->notify($notification);

		$this->mapper->delete($transferOwnership);
	}

	protected function getUser(string $userId): IUser {
		$user = $this->userManager->get($userId);
		if ($user instanceof IUser) {
			return $user;
		}
		throw new \InvalidArgumentException('User not found');
	}
}
