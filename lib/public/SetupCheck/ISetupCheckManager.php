<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\SetupCheck;

/**
 * @since 28.0.0
 */
interface ISetupCheckManager {
	/**
	 * Run all setup checks and return the results.
	 *
	 * @since 28.0.0
	 * @return array<string,array<string,SetupResult>> Result of each check, first level key is category, second level key is title
	 */
	public function runAll(): array;

	/**
	 * Run all tests from one specific category and return the results.
	 *
	 * @param string $filterByCategory - The id of the category to run.
	 * @return array<string,array<string,SetupResult>> Result of each check, first level key is category, second level key is title
	 * @since 35.0.0
	 */
	public function runByCategory(string $filterByCategory): array;

	/**
	 * Run all tests from one specific class and return the results.
	 *
	 * @param string $filterByClass - The class to run.
	 * @return array<string,array<string,SetupResult>> Result of each check, first level key is category, second level key is title
	 * @since 35.0.0
	 */
	public function runByClass(string $filterByClass): array;
}
