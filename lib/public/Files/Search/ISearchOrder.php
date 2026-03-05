<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Search;

use OCP\Files\FileInfo;

/**
 * @since 12.0.0
 */
interface ISearchOrder {
	/**
	 * @since 12.0.0
	 */
	public const DIRECTION_ASCENDING = 'asc';

	/**
	 * @since 12.0.0
	 */
	public const DIRECTION_DESCENDING = 'desc';

	/**
	 * The direction to sort in, either ISearchOrder::DIRECTION_ASCENDING or ISearchOrder::DIRECTION_DESCENDING
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getDirection(): string;

	/**
	 * The field to sort on
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getField(): string;

	/**
	 * extra means data are not related to the main files table
	 *
	 * @return string
	 * @since 28.0.0
	 */
	public function getExtra(): string;

	/**
	 * Apply the sorting on 2 FileInfo objects
	 *
	 * @param FileInfo $a
	 * @param FileInfo $b
	 * @return int -1 if $a < $b, 0 if $a = $b, 1 if $a > $b (for ascending, reverse for descending)
	 * @since 22.0.0
	 */
	public function sortFileInfo(FileInfo $a, FileInfo $b): int;
}
