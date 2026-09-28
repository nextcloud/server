<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\IntegrityCheck\Helpers;

/**
 * Class EnvironmentHelper provides a non-static helper for access to static
 * variables such as \OC::$SERVERROOT.
 *
 * @package OC\IntegrityCheck\Helpers
 */
class EnvironmentHelper {
	/**
	 * Provides \OC::$SERVERROOT
	 *
	 * @return string
	 */
	public function getServerRoot(): string {
		return rtrim(\OC::$SERVERROOT, '/');
	}
}
