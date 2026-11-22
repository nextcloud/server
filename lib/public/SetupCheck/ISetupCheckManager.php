<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\SetupCheck;

use OCP\Migration\IOutput;

/**
 * @since 28.0.0
 */
interface ISetupCheckManager {
	/**
	 * Run all setup checks and return the results.
	 *
	 * @param ?IOutput $output - Reports the check that is about to run as debug output, so a check that crashes or runs out of memory can be identified.
	 * @since 28.0.0
	 * @since 36.0.0 - parameter $output was added
	 * @return array<string,array<string,SetupResult>> Result of each check, first level key is category, second level key is title
	 */
	public function runAll(?IOutput $output = null): array;

	/**
	 * Run all tests from one specific category and return the results.
	 *
	 * @param string $filterByCategory - The id of the category to run.
	 * @param ?IOutput $output - Reports the check that is about to run as debug output, so a check that crashes or runs out of memory can be identified.
	 * @return array<string,array<string,SetupResult>> Result of each check, first level key is category, second level key is title
	 * @since 35.0.0
	 * @since 36.0.0 - parameter $output was added
	 */
	public function runByCategory(string $filterByCategory, ?IOutput $output = null): array;

	/**
	 * Run all tests from one specific class and return the results.
	 *
	 * @param string $filterByClass - The class to run.
	 * @param ?IOutput $output - Reports the check that is about to run as debug output, so a check that crashes or runs out of memory can be identified.
	 * @return array<string,array<string,SetupResult>> Result of each check, first level key is category, second level key is title
	 * @since 35.0.0
	 * @since 36.0.0 - parameter $output was added
	 */
	public function runByClass(string $filterByClass, ?IOutput $output = null): array;
}
