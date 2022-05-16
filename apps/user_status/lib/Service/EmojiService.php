<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\UserStatus\Service;

use OC\Comments\EmojiHelper;
use OCP\IDBConnection;

/**
 * Class EmojiService
 *
 * @package OCA\UserStatus\Service
 */
class EmojiService {

	/** @var IDBConnection */
	private $db;

	/**
	 * EmojiService constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	/**
	 * @return bool
	 */
	public function doesPlatformSupportEmoji(): bool {
		return $this->db->supports4ByteText() &&
			\class_exists(\IntlBreakIterator::class);
	}

	/**
	 * @param string $emoji
	 * @return bool
	 */
	public function isValidEmoji(string $emoji): bool {
		$emojiHelper = new EmojiHelper($this->db);
		return $emojiHelper->isValidEmoji($emoji);
	}
}
