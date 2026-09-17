<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Security;

use OCP\Security\ISecureRandom;
use Random\Randomizer;

/**
 * Class SecureRandom provides a wrapper around the random_int function to generate
 * secure random strings. This use the native CSPRNG.
 */
class SecureRandom implements ISecureRandom {
	#[\Override]
	public function generate(
		int $length,
		string $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/',
	): string {
		if ($length <= 0) {
			throw new \LengthException('Invalid length specified: ' . $length . ' must be bigger than 0');
		}

		/** @var non-empty-string $result */
		$result = (new Randomizer())->getBytesFromString($characters, $length);
		return $result;
	}
}
