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
namespace OC\Comments;

use OCP\IDBConnection;

/**
 * Copied OCA\UserStatus\Service\EmojiService
 * Needs to be unified later
 */
class EmojiHelper {

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
		$intlBreakIterator = \IntlBreakIterator::createCharacterInstance();
		$intlBreakIterator->setText($emoji);

		$characterCount = 0;
		while ($intlBreakIterator->next() !== \IntlBreakIterator::DONE) {
			$characterCount++;
		}

		if ($characterCount !== 1) {
			return false;
		}

		$codePointIterator = \IntlBreakIterator::createCodePointInstance();
		$codePointIterator->setText($emoji);

		foreach ($codePointIterator->getPartsIterator() as $codePoint) {
			$codePointType = \IntlChar::charType($codePoint);

			// If the current code-point is an emoji or a modifier (like a skin-tone)
			// just continue and check the next character
			if ($codePointType === \IntlChar::CHAR_CATEGORY_MODIFIER_SYMBOL ||
				$codePointType === \IntlChar::CHAR_CATEGORY_MODIFIER_LETTER ||
				$codePointType === \IntlChar::CHAR_CATEGORY_OTHER_SYMBOL ||
				$codePointType === \IntlChar::CHAR_CATEGORY_FORMAT_CHAR ||
				$codePointType === \IntlChar::CHAR_CATEGORY_OTHER_PUNCTUATION ||
				$codePointType === \IntlChar::CHAR_CATEGORY_LOWERCASE_LETTER ||
				$codePointType === \IntlChar::CHAR_CATEGORY_MATH_SYMBOL ||
				$codePointType === \IntlChar::CHAR_CATEGORY_ENCLOSING_MARK ||
				$codePointType === \IntlChar::CHAR_CATEGORY_DECIMAL_DIGIT_NUMBER ||
				$codePointType === \IntlChar::CHAR_CATEGORY_DASH_PUNCTUATION ||
				$codePointType === \IntlChar::CHAR_CATEGORY_GENERAL_OTHER_TYPES) {
				continue;
			}

			// If it's neither a modifier nor an emoji, we only allow
			// a zero-width-joiner or a variation selector 16
			$codePointValue = \IntlChar::ord($codePoint);
			if ($codePointValue === 8205 || $codePointValue === 65039) {
				continue;
			}

			return false;
		}

		return true;
	}
}
