<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib;

/**
 * Trait for objects that have dependencies for use
 */
trait DependencyTrait {

	/**
	 * Check if object is valid for use
	 *
	 * @return MissingDependency[] Unsatisfied dependencies
	 */
	public function checkDependencies() {
		return []; // no dependencies by default
	}
}
