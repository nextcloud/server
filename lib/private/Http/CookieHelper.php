<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Http;

class CookieHelper {
	public const SAMESITE_NONE = 0;
	public const SAMESITE_LAX = 1;
	public const SAMESITE_STRICT = 2;

	public static function setCookie(string $name,
		string $value = '',
		int $maxAge = 0,
		string $path = '',
		string $domain = '',
		bool $secure = false,
		bool $httponly = false,
		int $samesite = self::SAMESITE_NONE) {
		$header = sprintf(
			'Set-Cookie: %s=%s',
			$name,
			rawurlencode($value)
		);

		if ($path !== '') {
			$header .= sprintf('; Path=%s', $path);
		}

		if ($domain !== '') {
			$header .= sprintf('; Domain=%s', $domain);
		}

		if ($maxAge > 0) {
			$header .= sprintf('; Max-Age=%d', $maxAge);
		}

		if ($secure) {
			$header .= '; Secure';
		}

		if ($httponly) {
			$header .= '; HttpOnly';
		}

		if ($samesite === self::SAMESITE_LAX) {
			$header .= '; SameSite=Lax';
		} elseif ($samesite === self::SAMESITE_STRICT) {
			$header .= '; SameSite=Strict';
		}

		header($header, false);
	}
}
