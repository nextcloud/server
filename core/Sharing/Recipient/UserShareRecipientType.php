<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\Recipient;

use OC\Core\AppInfo\Application;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Sharing\Icon\ShareIconURL;

// TODO: Add delete listener to remove recipients
final class UserShareRecipientType extends AShareRecipientTypeSearchCollaborator {
	private ?IUserManager $userManager = null;

	private ?IGroupManager $groupManager = null;

	private ?IManager $legacyManager = null;

	private function getUserManager(): IUserManager {
		return $this->userManager ??= Server::get(IUserManager::class);
	}

	private function getGroupManager(): IGroupManager {
		return $this->groupManager ??= Server::get(IGroupManager::class);
	}

	private function getLegacyManager(): IManager {
		return $this->legacyManager ??= Server::get(IManager::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('User');
	}

	#[\Override]
	public function validateRecipient(IUser $owner, string $recipient): bool {
		if ($recipient === $owner->getUID()) {
			return false;
		}

		$recipientUser = $this->getUserManager()->get($recipient);
		if ($recipientUser === null) {
			return false;
		}

		if ($this->getLegacyManager()->shareWithGroupMembersOnly()) {
			$groups = array_intersect(
				$this->getGroupManager()->getUserGroupIds($owner),
				$this->getGroupManager()->getUserGroupIds($recipientUser),
			);
			if ($groups === []) {
				return false;
			}

			$groups = array_diff($groups, $this->getLegacyManager()->shareWithGroupMembersOnlyExcludeGroupsList());
			if ($groups === []) {
				return false;
			}
		}

		return true;
	}

	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (!$currentUser instanceof IUser) {
			return [];
		}

		return [$currentUser->getUID()];
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient): ?string {
		return $this->getUserManager()->getDisplayName($recipient);
	}

	#[\Override]
	public function getRecipientIcon(string $recipient): ShareIconURL {

		return new ShareIconURL(
			$this->getUserManager()->getAvatarUrlLight($recipient, 64),
			$this->getUserManager()->getAvatarUrlDark($recipient, 64),
		);
	}

	#[\Override]
	public function getCollaboratorType(): int {
		return IShare::TYPE_USER;
	}

	#[\Override]
	public function getCollaboratorKey(): string {
		return 'users';
	}
}
