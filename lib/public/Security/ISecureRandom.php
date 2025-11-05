<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Security;

/**
 * Secure random string generator for tokens, passwords, secrets, and similar security use cases.
 *
 * A wrapper around PHP's random_int(), utilizing the native CSPRNG.
 * @link https://www.php.net/manual/en/function.random-int.php
 *
 * By default, uses the RFC 4648 Base64 alphabet for random string generation, and allows
 * custom character sets if desired.
 *
 * Example usage:
 *   - Typical (if ISecureRandom $random is provided by DI):
 *       `$secret = $this->random->generate(48);`
 *   - Non-DI:
 *       `$secret = \OCP\Server::get(\OCP\Security\ISecureRandom::class)->generate(48);`
 * @since 8.0.0
 */
interface ISecureRandom {
	/**
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
	 * Lowercase, uppercase characters, and digits. Ambiguous characters are excluded (e.g., I, l, and 1).
	 * @since 23.0.0
	 */
	public const CHAR_HUMAN_READABLE = 'abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789';

	/**
	 * Standard Base64 alphabet per RFC4648.
	 * @link https://datatracker.ietf.org/doc/html/rfc4648#section-4
	 * @since 33.0.0
	 */
	public const CHAR_BASE64_RFC4648 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

	/**
	 * Generate a secure random string of the specified length.
	 *
	 * Security notes:
	 * - For most secure applications (tokens, passwords, CSRF values), an ample and diverse
	 *   character set, such as the default CHAR_BASE64_RFC4648, is typically a good choice.
	 * - Overly small (<4), non-unique, or multibyte character sets weaken security and are not permitted.
	 *
	 * @param int $length Number of characters (must be > 0).
	 * @param string $characters Optional list of unique, single-byte (ASCII) characters
	 *        to use. Defaults to the CHAR_BASE64_RFC4648 alphabet. A custom set should contain at least 4
	 *        characters, and must not contain duplicates or multibyte (non-ASCII) characters. It is strongly
	 *        recommended to use predefined constants from ISecureRandom, which all meet the requirements.
	 * @return string The randomly generated string.
	 * @throws \LengthException If $length <= 0.
	 * @throws \InvalidArgumentException if $characters contains non-ASCII characters, duplicates,
	 *         or fewer than 4 unique characters.
	 * @since 8.0.0
	 */
	public function generate(
		int $length,
		string $characters = ISecureRandom::CHAR_BASE64_RFC4648,
	): string;
}
