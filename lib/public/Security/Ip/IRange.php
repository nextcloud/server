<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\Ip;

/**
 * IP Range (IPv4 or IPv6)
 *
 * @since 30.0.0
 */
interface IRange {
	/**
	 * Check if a given range is valid
	 *
	 * @since 30.0.0
	 */
	public static function isValid(string $range): bool;

	/**
	 * Check if an address is in the current range
	 *
	 * @since 30.0.0
	 */
	public function contains(IAddress $address): bool;

	/**
	 * Normalized IP range
	 *
	 * @since 30.0.0
	 */
	public function __toString(): string;
}
