<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP;

/**
 * Service that find the binary path for a program.
 *
 * This interface should be injected via dependency injection and must
 * not be implemented in applications.
 *
 * @since 25.0.0
 */
interface IBinaryFinder {
	/**
	 * Try to find a program
	 *
	 * @return false|string
	 * @since 25.0.0
	 */
	public function findBinaryPath(string $program);
}
