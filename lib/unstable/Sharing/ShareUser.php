<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing;

use NCU\Sharing\Icon\ShareIconURL;
use OCP\AppFramework\Attribute\Consumable;
use OCP\IUserManager;
use RuntimeException;

/**
 * @psalm-import-type SharingUser from Share
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final readonly class ShareUser {
	/**
	 * @experimental 35.0.0
	 */
	public function __construct(
		/** @var non-empty-string $userId */
		public string $userId,
		/** @var ?non-empty-string $instance */
		public ?string $instance,
	) {
		if ($instance !== null && !preg_match('/^https?:\/\/.+/', $instance)) {
			throw new RuntimeException('The instance is not a valid absolute URL: ' . $instance);
		}
	}

	/**
	 * @experimental 35.0.0
	 */
	public function isCurrentUser(ShareAccessContext $accessContext): bool {
		return $this->instance === null && $accessContext->currentUser?->getUID() === $this->userId;
	}

	/**
	 * @return SharingUser
	 * @experimental 35.0.0
	 */
	public function format(IUserManager $userManager): array {
		if ($this->instance !== null) {
			// TODO: Support federation
			throw new RuntimeException('Currently only local users are supported.');
		}

		$displayName = $userManager->getDisplayName($this->userId);
		if ($displayName === null) {
			throw new RuntimeException('No display name for user ' . $this->userId);
		}

		return [
			'user_id' => $this->userId,
			'instance' => $this->instance,
			'display_name' => $displayName,
			'icon' => (new ShareIconURL(
				$userManager->getAvatarUrlLight($this->userId, 64),
				$userManager->getAvatarUrlDark($this->userId, 64),
			))->format(),
		];
	}
}
