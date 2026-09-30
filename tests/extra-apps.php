<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * App ids explicitly requested via EXTRA_APPS_PATHS, e.g.
 * EXTRA_APPS_PATHS=apps-extra/user_saml:apps-extra/twofactor_totp
 *
 * @return list<string>
 */
function getExtraAppIds(): array {
	$extraAppsPaths = getenv('EXTRA_APPS_PATHS');
	if ($extraAppsPaths === false || $extraAppsPaths === '') {
		return [];
	}

	$appIds = [];
	foreach (explode(PATH_SEPARATOR, $extraAppsPaths) as $extraAppsPath) {
		// relative paths are resolved against the server root, not the
		// current working directory, which differs between the stages
		// of a test run (repo root during install, tests/ for phpunit)
		if (!str_starts_with($extraAppsPath, '/')) {
			$extraAppsPath = \OC::$SERVERROOT . '/' . $extraAppsPath;
		}
		if (is_dir($extraAppsPath)) {
			$appIds[] = basename($extraAppsPath);
		}
	}

	return $appIds;
}
