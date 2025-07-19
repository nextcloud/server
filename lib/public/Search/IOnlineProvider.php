<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Search;

/**
 * Interface for online search providers that forward user queries to external services.
 *
 * @since 32.0.0
 */
interface IOnlineProvider {
	/**
	 * Indicates whether this search provider queries online (external) resources.
	 * This is used by the Unified Search modal filter (toggle switch). By default, searching through online providers is disabled.
	 *
	 * @return bool default false
	 *
	 * @since 32.0.0
	 */
	public function getIsOnlineResource(): bool;
}
