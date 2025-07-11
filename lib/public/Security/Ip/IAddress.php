<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\Ip;

/**
 * @since 30.0.0
 */
interface IAddress {
	/**
	 * Check if a given IP address is valid
	 *
	 * @since 30.0.0
	 */
	public static function isValid(string $ip): bool;

	/**
	 * Check if current address is contained by given ranges
	 *
	 * @since 30.0.0
	 */
	public function matches(IRange ... $ranges): bool;

	/**
	 * Normalized IP address
	 *
	 * @since 30.0.0
	 */
	public function __toString(): string;
}
