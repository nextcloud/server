<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\Recipient;

use Exception;
use NCU\Sharing\Icon\ShareIconURL;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\Recipient\AShareRecipientTypeSearchCollaborator;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Recipient\TShareRecipientTypeDisplayNameAddressBook;
use NCU\Sharing\ShareAccessContext;
use OC\Core\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IDBConnection;
use OCP\Interaction\InteractionReceiver;
use OCP\Interaction\Receivers\UserReceiver;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Share\IShare;
use OCP\User\Events\BeforeUserDeletedEvent;

/**
 * @template-implements IEventListener<BeforeUserDeletedEvent>
 */
final class UserShareRecipientType extends AShareRecipientTypeSearchCollaborator implements IEventListener {
	use TShareRecipientTypeDisplayNameAddressBook;

	public function __construct(
		IEventDispatcher $eventDispatcher,
		private readonly IDBConnection $dbConnection,
		private readonly IUserManager $userManager,
		private readonly ISharingManager $manager,
	) {
		$eventDispatcher->addServiceListener(BeforeUserDeletedEvent::class, self::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('User');
	}

	#[\Override]
	public function validateRecipient(string $recipient): bool {
		return $this->userManager->userExists($recipient);
	}

	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (!$currentUser instanceof IUser) {
			return [];
		}

		return [$currentUser->getUID()];
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient, ?string $instance): ?string {
		if ($instance !== null) {
			return $this->getRecipientDisplayNameFromAddressBook($recipient . '@' . $instance, 'CLOUD');
		}

		return $this->userManager->getDisplayName($recipient);
	}

	#[\Override]
	public function getRecipientIcon(string $recipient, ?string $instance): ShareIconURL {
		// The avatar endpoints automatically handle avatars of remote users.
		$id = $recipient;
		if ($instance !== null) {
			// The instance could contain https:// or http://
			$id .= '@' . urlencode($instance);
		}

		return new ShareIconURL(
			$this->userManager->getAvatarUrlLight($id, 64),
			$this->userManager->getAvatarUrlDark($id, 64),
		);
	}

	#[\Override]
	public function getRecipientInteractionReceiver(string $recipient): InteractionReceiver {
		return new UserReceiver($recipient);
	}

	#[\Override]
	public function getCollaboratorType(): int {
		return IShare::TYPE_USER;
	}

	#[\Override]
	public function getCollaboratorKey(): string {
		return 'users';
	}

	#[\Override]
	public function handle(Event $event): void {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->onRecipientDeleted(new ShareAccessContext(overrideChecks: true), new ShareRecipient(self::class, $event->getUser()->getUID(), null));
			$this->dbConnection->commit();
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw $exception;
		}
	}
}
