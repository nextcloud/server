<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Utility;

/**
 * Evicts services marked with {@see \OCP\AppFramework\Attribute\PersistAcrossRequests}
 * that declared a dependency on the given group, across every worker process.
 *
 * Call this whenever something changes that a persisted service depends on
 * (e.g. an app-specific setting a persisted service reads at construction
 * time). Use a {@see PersistentServiceGroup} case for a cause shared with
 * core, or any string for something specific to your own service, as long
 * as it matches the one used in
 * {@see \OCP\AppFramework\Attribute\PersistAcrossRequests::$invalidatedBy}.
 *
 * @since 36.0.0
 */
interface IPersistentServiceInvalidator {
	/**
	 * @since 36.0.0
	 */
	public function invalidate(string|PersistentServiceGroup $group): void;
}
