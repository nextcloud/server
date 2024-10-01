<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Collaboration\AutoComplete;

/**
 * Interface IManager
 *
 * @since 13.0.0
 */
interface IManager {
	/**
	 * @param string $className – class name of the ISorter implementation
	 * @since 13.0.0
	 */
	public function registerSorter($className);

	/**
	 * @param array $sorters list of sorter IDs, separated by "|"
	 * @param array $sortArray array representation of OCP\Collaboration\Collaborators\ISearchResult
	 * @param array{itemType: string, itemId: string, search?: string} $context context info of the search
	 * @since 13.0.0
	 */
	public function runSorters(array $sorters, array &$sortArray, array $context);
}
