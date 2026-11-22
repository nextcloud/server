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
interface IFactory {
	/**
	 * Creates a range from string
	 *
	 * @since 30.0.0
	 * @throws \InvalidArgumentException on invalid range
	 */
	public function rangeFromString(string $range): IRange;

	/**
	 * Creates a address from string
	 *
	 * @since 30.0.0
	 * @throws \InvalidArgumentException on invalid IP
	 */
	public function addressFromString(string $ip): IAddress;
}
