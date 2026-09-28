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
	 * @since 28.0.0
	 * @return array<string,array<string,SetupResult>> Result of each check, first level key is category, second level key is title
	 */
	public function runAll(): array;
}
