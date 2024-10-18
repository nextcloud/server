<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus;

use OCP\Capabilities\ICapability;
use OCP\IEmojiHelper;

/**
 * Class Capabilities
 *
 * @package OCA\UserStatus
 */
class Capabilities implements ICapability {
	public function __construct(
		private IEmojiHelper $emojiHelper,
	) {
	}

	/**
	 * @return array{user_status: array{enabled: bool, restore: bool, supports_emoji: bool}}
	 */
	public function getCapabilities() {
		return [
			'user_status' => [
				'enabled' => true,
				'restore' => true,
				'supports_emoji' => $this->emojiHelper->doesPlatformSupportEmoji(),
			],
		];
	}
}
