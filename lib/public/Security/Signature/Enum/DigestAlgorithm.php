<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\Signature\Enum;

use OCP\AppFramework\Attribute\Consumable;

/**
 * list of available algorithm when generating digest from body
 *
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
enum DigestAlgorithm: string {
	/** @since 33.0.0 */
	case SHA256 = 'SHA-256';
	/** @since 33.0.0 */
	case SHA512 = 'SHA-512';

	/**
	 * returns hashing algorithm to be used when generating digest
	 *
	 * @return string
	 * @since 33.0.0
	 */
	public function getHashingAlgorithm(): string {
		return match($this) {
			self::SHA256 => 'sha256',
			self::SHA512 => 'sha512',
		};
	}
}
