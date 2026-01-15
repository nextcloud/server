<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Security;

use OCP\Security\ISecureRandom;

/**
 * Secure random string generator recommended for tokens, passwords, secrets, and similar security use cases.
 *
 * @see \OCP\Security\ISecureRandom
 */
class SecureRandom implements ISecureRandom {

	/** @inheritdoc */
	public function generate(
		int $length,
		string $characters = ISecureRandom::CHAR_BASE64_RFC4648,
	): string {

		if ($length <= 0) {
			throw new \LengthException(
				'Invalid length specified: ' . $length . ' must be greater than 0'
			);
		}

		if (
			// Check for ASCII-only (no multibyte characters)
			!mb_check_encoding($characters, 'ASCII')
			// Check for uniqueness: number of unique bytes must equal original length
			|| strlen(count_chars($characters, 3)) !== strlen($characters)
			// Check minimum length
			|| strlen($characters) < 4
		) {
			throw new \InvalidArgumentException(
				'Character set must be ASCII-only, unique, and at least four characters long.'
			);
		}

		// Build string by selecting random characters from $characters and appending
		$maxCharIndex = \strlen($characters) - 1;
		$randomString = '';
		while ($length > 0) {
			$randomNumber = \random_int(0, $maxCharIndex);
			// Safe: $characters is guaranteed ASCII; indexed access is byte-correct.
			$randomString .= $characters[$randomNumber];
			$length--;
		}
		return $randomString;
	}
}
