<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature\Enum;

/**
 * list of available algorithm when generating digest from body
 *
 * @experimental 31.0.0
 */
enum DigestAlgorithm: string {
	/** @experimental 31.0.0 */
	case SHA256 = 'SHA-256';
	/** @experimental 31.0.0 */
	case SHA512 = 'SHA-512';

	/**
	 * returns hashing algorithm to be used when generating digest
	 *
	 * @return string
	 * @experimental 31.0.0
	 */
	public function getHashingAlgorithm(): string {
		return match($this) {
			self::SHA256 => 'sha256',
			self::SHA512 => 'sha512',
		};
	}
}
