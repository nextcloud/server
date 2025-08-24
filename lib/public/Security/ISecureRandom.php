<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Security;

/**
 * Class SecureRandom provides a wrapper around the random_int function to generate
 * secure random strings. For PHP 7 the native CSPRNG is used, older versions do
 * use a fallback.
 *
 * Usage:
 * \OCP\Server::get(ISecureRandom::class)->generate(10);
 *
 * @since 8.0.0
 */
interface ISecureRandom {
	/**
	 * Flags for characters that can be used for <code>generate($length, $characters)</code>
	 * @since 8.0.0
	 */
	public const CHAR_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

	/**
	 * @since 8.0.0
	 */
	public const CHAR_LOWER = 'abcdefghijklmnopqrstuvwxyz';

	/**
	 * @since 8.0.0
	 */
	public const CHAR_DIGITS = '0123456789';

	/**
	 * @since 8.0.0
	 */
	public const CHAR_SYMBOLS = '!\"#$%&\\\'()*+,-./:;<=>?@[\]^_`{|}~';

	/**
	 * @since 12.0.0
	 */
	public const CHAR_ALPHANUMERIC = self::CHAR_UPPER . self::CHAR_LOWER . self::CHAR_DIGITS;

	/**
	 * Characters that can be used for <code>generate($length, $characters)</code>, to
	 * generate human-readable random strings. Lower- and upper-case characters and digits
	 * are included. Characters which are ambiguous are excluded, such as I, l, and 1 and so on.
	 *
	 * @since 23.0.0
	 */
	public const CHAR_HUMAN_READABLE = 'abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789';

	/**
	 * Generate a random string of specified length.
	 * @param int $length The length of the generated string
	 * @param string $characters An optional list of characters to use if no character list is
	 *                           specified all valid base64 characters are used.
	 * @return string
	 * @since 8.0.0
	 */
	public function generate(int $length,
		string $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'): string;
}
