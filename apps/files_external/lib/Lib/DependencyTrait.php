<?php

declare(strict_types=1);

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
	 * Check if object has unsatisfied required or optional dependencies
	 *
	 * @return MissingDependency[] Unsatisfied dependencies
	 */
	public function checkDependencies() {
		return []; // no dependencies by default
	}

	/**
	 * Check if object has unsatisfied required dependencies
	 *
	 * @return MissingDependency[] Unsatisfied required dependencies
	 */
	public function checkRequiredDependencies(): array {
		return array_filter(
			$this->checkDependencies(),
			fn (MissingDependency $dependency) => !$dependency->isOptional()
		);
	}
}
