<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Template;

/**
 * @since 32.0.0
 */
interface ITemplate {
	/**
	 * Process the template
	 * @since 32.0.0
	 */
	public function fetchPage(?array $additionalParams = null): string;

	/**
	 * Proceed the template and print its output.
	 * @since 32.0.0
	 */
	public function printPage(): void;

	/**
	 * Assign variables
	 *
	 * This function assigns a variable. It can be accessed via $_[$key] in
	 * the template.
	 *
	 * If the key existed before, it will be overwritten
	 * @since 32.0.0
	 */
	public function assign(string $key, float|array|bool|int|string|\Throwable $value): void;
}
