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
	// ICU UProperty values, not exposed as IntlChar::PROPERTY_* constants
	private const UCHAR_EMOJI = 57;
	private const UCHAR_EMOJI_COMPONENT = 61;
	private const UCHAR_EXTENDED_PICTOGRAPHIC = 64;

	public function __construct(
		private IDBConnection $db,
	) {
	}

	#[\Override]
	public function doesPlatformSupportEmoji(): bool {
		return $this->db->supports4ByteText()
			&& \class_exists(\IntlBreakIterator::class);
	}

	#[\Override]
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

		// Plain ASCII (digits, '#', '*') has the Emoji property, but is not an emoji on its own
		if (strlen($emoji) < 2) {
			return false;
		}

		$codePointIterator = \IntlBreakIterator::createCodePointInstance();
		$codePointIterator->setText($emoji);

		foreach ($codePointIterator->getPartsIterator() as $codePoint) {
			$codePointValue = \IntlChar::ord($codePoint);

			// Every code-point must be an emoji, a component (ZWJ, VS16, keycap, tag, skin-tone)
			// or a pictographic (includes code-points reserved for future emoji)
			if (!\IntlChar::hasBinaryProperty($codePointValue, self::UCHAR_EMOJI)
				&& !\IntlChar::hasBinaryProperty($codePointValue, self::UCHAR_EMOJI_COMPONENT)
				&& !\IntlChar::hasBinaryProperty($codePointValue, self::UCHAR_EXTENDED_PICTOGRAPHIC)
			) {
				return false;
			}
		}

		return true;
	}
}
