<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\IntegrityCheck\Helpers;

/**
 * Class AppLocator provides a non-static helper for OC_App::getPath($appId)
 * it is not possible to use IAppManager at this point as IAppManager has a
 * dependency on a running Nextcloud.
 *
 * @package OC\IntegrityCheck\Helpers
 */
class AppLocator {
	/**
	 * Provides \OC_App::getAppPath($appId)
	 *
	 * @param string $appId
	 * @return string
	 * @throws \Exception If the app cannot be found
	 */
	public function getAppPath(string $appId): string {
		$path = \OC_App::getAppPath($appId);
		if ($path === false) {
			throw new \Exception('App not found');
		}
		return $path;
	}
}
