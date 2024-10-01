<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Collaboration\AutoComplete;

/**
 * Interface ISorter
 *
 * Sorts the list of .e.g users for auto completion
 *
 * @since 13.0.0
 */
interface ISorter {
	/**
	 * @return string The ID of the sorter, e.g. commenters
	 * @since 13.0.0
	 */
	public function getId();

	/**
	 * executes the sort action
	 *
	 * @param array $sortArray the array to be sorted, provided as reference
	 * @param array{itemType: string, itemId: string, search?: string} $context carries key 'itemType' and 'itemId' of the source object (e.g. a file)
	 * @since 13.0.0
	 */
	public function sort(array &$sortArray, array $context);
}
