<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Calendar\Resource;

/**
 * @since 14.0.0
 */
interface IManager {
	/**
	 * Update all resources from all backends right now.
	 *
	 * @since 30.0.0
	 */
	public function update(): void;
}
