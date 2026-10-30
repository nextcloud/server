<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Import;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Marks changes as an import: content that already exists elsewhere (another
 * system being migrated, a backup being restored) is written into Nextcloud
 * on behalf of its users.
 *
 * Users don't need to be told about content they already know, and an import
 * can write millions of files and shares. While an import runs, components
 * that inform users about changes skip doing so for the changes it makes:
 * no activity entries, no notifications, no share mails, no push events and
 * no "recently contacted" entries.
 * Everything else (events, hooks, file cache, versions, …) works as usual.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IImportScope {
	/**
	 * Runs the callback as an import and returns its result. Calls can be
	 * nested; the import ends when the outermost callback returns or throws.
	 *
	 * @template T
	 * @param callable(): T $callback
	 * @return T
	 * @since 36.0.0
	 */
	public function run(callable $callback): mixed;

	/**
	 * Whether the code that runs now is part of an import. Components that
	 * inform users about changes check this and skip informing them.
	 *
	 * @since 36.0.0
	 */
	public function isActive(): bool;
}
