<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Navigation\Events;

use OCP\EventDispatcher\Event;

/**
 * Dispatched when the navigation entries are read, before they are sorted,
 * so an app can remove entries the current user should not be offered.
 *
 * Entries can only be removed: an entry added or changed here is ignored.
 * Use LoadAdditionalEntriesEvent to add one. Only dispatched when an app
 * listens for it.
 *
 * @since 36.0.0
 */
class NavigationEntriesFilterEvent extends Event {
	/**
	 * @param array<string, array> $entries the entries, keyed by their id
	 * @param string $type the type that was asked for, see INavigationManager::TYPE_*
	 * @since 36.0.0
	 */
	public function __construct(
		private array $entries,
		private string $type,
	) {
		parent::__construct();
	}

	/**
	 * @return array<string, array> the entries, keyed by their id
	 * @since 36.0.0
	 */
	public function getEntries(): array {
		return $this->entries;
	}

	/**
	 * @param array<string, array> $entries the entries to keep, keyed by their id
	 * @since 36.0.0
	 */
	public function setEntries(array $entries): void {
		$this->entries = $entries;
	}

	/**
	 * The type of entries that was asked for, see INavigationManager::TYPE_*
	 *
	 * @since 36.0.0
	 */
	public function getType(): string {
		return $this->type;
	}
}
