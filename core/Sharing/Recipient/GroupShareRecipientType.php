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
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\ShareAccessContext;

// TODO: Add delete listener to remove recipients
final class GroupShareRecipientType extends AShareRecipientTypeSearchCollaborator {
	private ?IGroupManager $groupManager = null;

	private ?IManager $legacyManager = null;

	private function getGroupManager(): IGroupManager {
		return $this->groupManager ??= Server::get(IGroupManager::class);
	}

	private function getLegacyManager(): IManager {
		return $this->legacyManager ??= Server::get(IManager::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Group');
	}

	#[\Override]
	public function validateRecipient(IUser $owner, string $recipient): bool {
		if (!$this->getLegacyManager()->allowGroupSharing()) {
			return false;
		}

		return $this->getGroupManager()->groupExists($recipient);
	}

	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (!$currentUser instanceof IUser) {
			return [];
		}

		return $this->getGroupManager()->getUserGroupIds($currentUser);
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient): ?string {
		$displayName = $this->getGroupManager()->getDisplayName($recipient);
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
	public function searchRecipients(ShareAccessContext $accessContext, string $query, int $limit, int $offset): array {
		if (!$this->getLegacyManager()->allowGroupSharing()) {
			return [];
		}

		return parent::searchRecipients($accessContext, $query, $limit, $offset);
	}

	#[\Override]
	public function getCollaboratorType(): int {
		return IShare::TYPE_GROUP;
	}

	#[\Override]
	public function getCollaboratorKey(): string {
		return 'groups';
	}
}
