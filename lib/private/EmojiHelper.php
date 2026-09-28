<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC;

use OCP\IDBConnection;
use OCP\IEmojiHelper;

class EmojiHelper implements IEmojiHelper {
	private IDBConnection $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	public function doesPlatformSupportEmoji(): bool {
		return $this->db->supports4ByteText()
			&& \class_exists(\IntlBreakIterator::class);
	}

	public function isValidSingleEmoji(string $emoji): bool {
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

			// Unicode chars need 2 or more chars
			// The characterCount before this loop already validate if is a single emoji
			// This condition is to don't continue if non emoji chars
			if (strlen($emoji) >= 2) {
				// If the current code-point is an emoji or a modifier (like a skin-tone)
				// just continue and check the next character
				if ($codePointType === \IntlChar::CHAR_CATEGORY_MODIFIER_SYMBOL
					|| $codePointType === \IntlChar::CHAR_CATEGORY_MODIFIER_LETTER
					|| $codePointType === \IntlChar::CHAR_CATEGORY_OTHER_SYMBOL
					|| $codePointType === \IntlChar::CHAR_CATEGORY_FORMAT_CHAR          // i.e. 🏴󠁧󠁢󠁥󠁮󠁧󠁿 🏴󠁧󠁢󠁳󠁣󠁴󠁿
					|| $codePointType === \IntlChar::CHAR_CATEGORY_OTHER_PUNCTUATION    // i.e. ‼️ ⁉️ #⃣
					|| $codePointType === \IntlChar::CHAR_CATEGORY_LOWERCASE_LETTER     // i.e. ℹ️
					|| $codePointType === \IntlChar::CHAR_CATEGORY_MATH_SYMBOL          // i.e. ↔️ ◻️ ⤴️ ⤵️
					|| $codePointType === \IntlChar::CHAR_CATEGORY_ENCLOSING_MARK       // i.e. 0⃣..9⃣
					|| $codePointType === \IntlChar::CHAR_CATEGORY_DECIMAL_DIGIT_NUMBER // i.e. 0⃣..9⃣
					|| $codePointType === \IntlChar::CHAR_CATEGORY_DASH_PUNCTUATION     // i.e. 〰️
					|| $codePointType === \IntlChar::CHAR_CATEGORY_GENERAL_OTHER_TYPES
				) {
					continue;
				}
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
