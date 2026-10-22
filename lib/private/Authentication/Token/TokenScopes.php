<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Token;

use OCA\Files\TokenScopes as FilesTokenScopes;
use OCP\Authentication\Token\IToken;

/**
 * Scopes a token is limited to. A token without a `scopes` key has full access.
 *
 * Apps define the scopes for their own data, and each check names the one scope it needs,
 * so a scope nothing checks grants nothing.
 */
class TokenScopes {
	public const KEY = 'scopes';

	private const MAX_SCOPES = 100;
	private const MAX_SCOPE_LENGTH = 64;

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
	 * @param list<string> $grantableApps
	 * @throws \InvalidArgumentException
	 */
	public function updateScope(array $stored, array $requested, array $grantableApps): array {
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
				// Grants already stored stay, so an app that left the navigation doesn't block saving
				$appGrants = [...$grantableApps, ...($this->fromBlob($stored) ?? [])];
				$blob[self::KEY] = $this->validate($requestedScopes, $appGrants);
			}
		}

		$scopes = $this->fromBlob($blob);
		if ($scopes !== null) {
			$blob[IToken::SCOPE_FILESYSTEM] = in_array(FilesTokenScopes::FILES_READ, $scopes, true);
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
	 * @param list<string> $appGrants app ids allowed as whole-app grants
	 * @return list<string>
	 * @throws \InvalidArgumentException
	 */
	private function validate(array $scopes, array $appGrants): array {
		if (count($scopes) > self::MAX_SCOPES) {
			throw new \InvalidArgumentException('Too many scopes');
		}
		foreach ($scopes as $scope) {
			if (!is_string($scope) || $scope === '' || strlen($scope) > self::MAX_SCOPE_LENGTH) {
				throw new \InvalidArgumentException('Invalid scope');
			}
			// A scope without a colon grants a whole app
			if (!str_contains($scope, ':') && !in_array($scope, $appGrants, true)) {
				throw new \InvalidArgumentException('Invalid scope');
			}
		}
		// Matched by app id: files_sharing can be disabled, and its classes don't load then
		$files = array_filter($scopes, static fn (string $scope) => in_array(strstr($scope, ':', true) ?: $scope, ['files', 'files_sharing', 'files_trashbin', 'files_versions'], true));
		// Files endpoints look nodes up by ID or path, so every files scope reads file data
		if ($files !== [] && !in_array(FilesTokenScopes::FILES_READ, $files, true)) {
			throw new \InvalidArgumentException('Files scopes require ' . FilesTokenScopes::FILES_READ);
		}
		// Read-only files need a permissions mask on the mounts, which does not exist yet
		if ($files !== [] && !in_array(FilesTokenScopes::FILES_WRITE, $files, true)) {
			throw new \InvalidArgumentException(FilesTokenScopes::FILES_READ . ' requires ' . FilesTokenScopes::FILES_WRITE);
		}

		$scopes = array_unique($scopes);
		sort($scopes);
		return $scopes;
	}
}
