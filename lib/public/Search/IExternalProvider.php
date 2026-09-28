<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Search;

/**
 * Interface for search providers that forward user queries to external services.
 *
 * @since 32.0.0
 */
interface IExternalProvider extends IProvider {
	/**
	 * Indicates whether this search provider queries external (3rd-party) resources.
	 * This is used by the Unified Search modal filter (toggle switch). By default, searching through external providers is disabled.
	 *
	 * @return bool default false
	 *
	 * @since 32.0.0
	 */
	public function isExternalProvider(): bool;
}
