<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP;

use OCP\AppFramework\Attribute\Consumable;

/**
 * @since 34.0.1
 */
#[Consumable(since: '34.0.1')]
interface IServerInfo {
	/**
	 * Returns configured Server ID or use default fallback
	 *
	 * @return int<0,511>
	 * @since 34.0.1
	 */
	public function getServerId(): int;

	/**
	 * Returns current server hostname
	 *
	 * @since 34.0.1
	 */
	public function getHostname(): string;
}
