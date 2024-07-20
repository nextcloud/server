<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\UserMigration;

use OCP\IUser;

/**
 * @since 25.0.0
 */
interface ISizeEstimationMigrator {
	/**
	 * Returns an estimate of the exported data size in KiB.
	 * Should be fast, favor performance over accuracy.
	 *
	 * @since 25.0.0
	 * @since 27.0.0 return value may overflow from int to float
	 */
	public function getEstimatedExportSize(IUser $user): int|float;
}
