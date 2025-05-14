<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\L10N;

/**
 * Interface ILanguageIterator
 *
 * iterator across language settings (if provided) in this order:
 * 1. returns the forced language or:
 * 2. if applicable, the trunk of 1 (e.g. "fu" instead of "fu_BAR"
 * 3. returns the user language or:
 * 4. if applicable, the trunk of 3
 * 5. returns the system default language or:
 * 6. if applicable, the trunk of 5
 * 7+∞. returns 'en'
 *
 * if settings are not present or truncating is not applicable, the iterator
 * skips to the next valid item itself
 *
 * @template-extends \Iterator<int, string>
 * @since 14.0.0
 */
interface ILanguageIterator extends \Iterator {
	/**
	 * Return the current element
	 *
	 * @since 14.0.0
	 */
	public function current(): string;

	/**
	 * Move forward to next element
	 *
	 * @since 14.0.0
	 */
	public function next(): void;

	/**
	 * Return the key of the current element
	 *
	 * @since 14.0.0
	 */
	public function key(): int;

	/**
	 * Checks if current position is valid
	 *
	 * @since 14.0.0
	 */
	public function valid(): bool;
}
