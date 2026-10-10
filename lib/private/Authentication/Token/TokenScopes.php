<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Token;

use OCP\Authentication\Token\IToken;

/**
 * Scopes a token is limited to. A token without a `scopes` key has full access.
 */
class TokenScopes {
	public const KEY = 'scopes';

	public const FILES_READ = 'files:read';
	public const FILES_WRITE = 'files:write';
	public const FILES_SHARE = 'files:share';
	public const CALENDAR_READ = 'calendar:read';
	public const CALENDAR_WRITE = 'calendar:write';
	public const CONTACTS_READ = 'contacts:read';
	public const CONTACTS_WRITE = 'contacts:write';

	/** Every scope a client may set, mapped to the scope it requires */
	private const VOCABULARY = [
		// Read-only files need a permissions mask on the mounts, which does not exist yet
		self::FILES_READ => self::FILES_WRITE,
		self::FILES_WRITE => self::FILES_READ,
		self::FILES_SHARE => self::FILES_READ,
		self::CALENDAR_READ => null,
		self::CALENDAR_WRITE => self::CALENDAR_READ,
		self::CONTACTS_READ => null,
		self::CONTACTS_WRITE => self::CONTACTS_READ,
	];

	/**
	 * @return list<string>|null null for full access, an empty list if the stored scopes are malformed
	 */
	public function fromBlob(array $blob): ?array {
		if (!array_key_exists(self::KEY, $blob)) {
			return null;
		}
		$scopes = $blob[self::KEY];
		return is_array($scopes) && $scopes === array_filter($scopes, is_string(...)) ? array_values($scopes) : [];
	}

	/**
	 * Only `filesystem` and `scopes` are taken from `$requested`. Omitted `scopes` stay as stored,
	 * `null` means full access, and scoped tokens get `filesystem` derived from their scopes.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function mergeUpdate(array $stored, array $requested): array {
		$blob = $stored;

		if (array_key_exists(self::KEY, $requested)) {
			$requestedScopes = $requested[self::KEY];
			if ($requestedScopes === null) {
				unset($blob[self::KEY]);
				$blob[IToken::SCOPE_FILESYSTEM] = true;
				return $blob;
			}
			if ($requestedScopes !== ($stored[self::KEY] ?? null)) {
				if (!is_array($requestedScopes)) {
					throw new \InvalidArgumentException('scopes must be an array');
				}
				$blob[self::KEY] = $this->validate($requestedScopes);
			}
		}

		$scopes = $this->fromBlob($blob);
		if ($scopes !== null) {
			$blob[IToken::SCOPE_FILESYSTEM] = in_array(self::FILES_READ, $scopes, true);
		} elseif (array_key_exists(IToken::SCOPE_FILESYSTEM, $requested)) {
			if (!is_bool($requested[IToken::SCOPE_FILESYSTEM])) {
				throw new \InvalidArgumentException('filesystem must be a boolean');
			}
			$blob[IToken::SCOPE_FILESYSTEM] = $requested[IToken::SCOPE_FILESYSTEM];
		} else {
			$blob[IToken::SCOPE_FILESYSTEM] ??= true;
		}

		return $blob;
	}

	/**
	 * @return list<string>
	 * @throws \InvalidArgumentException
	 */
	private function validate(array $scopes): array {
		foreach ($scopes as $scope) {
			if (!is_string($scope) || !array_key_exists($scope, self::VOCABULARY)) {
				throw new \InvalidArgumentException('Unknown scope');
			}
			$required = self::VOCABULARY[$scope];
			if ($required !== null && !in_array($required, $scopes, true)) {
				throw new \InvalidArgumentException($scope . ' requires ' . $required);
			}
		}

		$scopes = array_unique($scopes);
		sort($scopes);
		return $scopes;
	}
}
