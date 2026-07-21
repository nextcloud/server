<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\Recipient;

use Exception;
use OC\Core\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\Interaction\InteractionReceiver;
use OCP\Interaction\Receivers\GroupReceiver;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Share\IShare;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\Recipient\AShareRecipientTypeSearchCollaborator;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\ShareAccessContext;

/**
 * @template-implements IEventListener<GroupDeletedEvent>
 */
final class GroupShareRecipientType extends AShareRecipientTypeSearchCollaborator implements IEventListener {
	public function __construct(
		IEventDispatcher $eventDispatcher,
		private readonly IDBConnection $dbConnection,
		private readonly IGroupManager $groupManager,
		private readonly ISharingManager $manager,
	) {
		$eventDispatcher->addServiceListener(GroupDeletedEvent::class, self::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Group');
	}

	#[\Override]
	public function validateRecipient(string $recipient): bool {
		return $this->groupManager->groupExists($recipient);
	}

	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (!$currentUser instanceof IUser) {
			return [];
		}

		return $this->groupManager->getUserGroupIds($currentUser);
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient): ?string {
		$displayName = $this->groupManager->getDisplayName($recipient);
		if ($displayName === '') {
			return null;
		}

		return $displayName;
	}

	#[\Override]
	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		return null;
	}

	#[\Override]
	public function getRecipientInteractionReceiver(string $recipient): InteractionReceiver {
		return new GroupReceiver($recipient);
	}

	#[\Override]
	public function getCollaboratorType(): int {
		return IShare::TYPE_GROUP;
	}

	#[\Override]
	public function getCollaboratorKey(): string {
		return 'groups';
	}

	#[\Override]
	public function handle(Event $event): void {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->onRecipientDeleted(new ShareAccessContext(overrideChecks: true), new ShareRecipient(self::class, $event->getGroup()->getGID(), null));
			$this->dbConnection->commit();
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw $exception;
		}
	}
}
