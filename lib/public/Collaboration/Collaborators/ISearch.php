<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Collaboration\Collaborators;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Collaboration\AutoComplete\AutoCompleteFilterEvent;
use OCP\Share\IShare;
use Psr\Container\ContainerExceptionInterface;

/**
 * Interface ISearch
 *
 * @since 13.0.0
 */
#[Consumable(since: '13.0.0')]
interface ISearch {
	/**
	 * Search for autocompletion entries.
	 *
	 * After calling this function, users must emit the AutoCompleteFilter event to allow external
	 * apps to filter the results.
	 *
	 * @param string $search
	 * @param array $shareTypes
	 * @param bool $lookup
	 * @param int $limit
	 * @param int $offset
	 * @return array{array, bool} with two elements, 1st ISearchResult as array, 2nd a bool indicating whether more result are available
	 * @since 13.0.0
	 * @deprecated 35.0.0 Use filteredSearch instead.
	 * @see AutoCompleteFilterEvent
	 * @throws ContainerExceptionInterface
	 */
	public function search(string $search, array $shareTypes, bool $lookup, int $limit, int $offset): array;

	/**
	 * Search for autocompletion entries.
	 *
	 * This takes care of filtering the returned users depending on the current user's permissions.
	 *
	 * @param string $search
	 * @param list<IShare::TYPE_*> $shareTypes
	 * @param bool $lookup
	 * @param int $limit
	 * @param int $offset
	 * @param ?string $itemType main identifier of the search to add some additional context (e.g. 'call', 'calendar')
	 * @param ?string $itemId sub-identifier of the search
	 * @return array{array, bool} with two elements, 1st ISearchResult as array, 2nd a bool indicating whether more result are available
	 * @since 35.0.0
	 * @throws ContainerExceptionInterface
	 */
	public function filteredSearch(string $search, array $shareTypes, bool $lookup, ?string $itemType, ?string $itemId, int $limit, int $offset): array;

	/**
	 * @param array{shareType: IShare::TYPE_*, class: class-string<ISearchPlugin>} $pluginInfo
	 *                                                                                         with keys 'shareType' containing the name of a corresponding constant in \OCP\IShare and
	 *                                                                                         'class' with the class name of the plugin
	 * @since 13.0.0
	 * @note Due to legacy reasons, shareType also accept a string of the for 'SHARE_TYPE_*' matching the constants from IShare::TYPE_*
	 */
	public function registerPlugin(array $pluginInfo): void;
}
