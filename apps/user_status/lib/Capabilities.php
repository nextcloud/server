<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	private IEmojiHelper $emojiHelper;

	public function __construct(IEmojiHelper $emojiHelper) {
		$this->emojiHelper = $emojiHelper;
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
