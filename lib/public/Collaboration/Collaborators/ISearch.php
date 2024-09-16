<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Collaboration\Collaborators;

/**
 * Interface ISearch
 *
 * @since 13.0.0
 */
interface ISearch {
	/**
	 * @param string $search
	 * @param array $shareTypes
	 * @param bool $lookup
	 * @param int $limit
	 * @param int $offset
	 * @return array with two elements, 1st ISearchResult as array, 2nd a bool indicating whether more result are available
	 * @since 13.0.0
	 */
	public function search($search, array $shareTypes, $lookup, $limit, $offset);

	/**
	 * @param array $pluginInfo with keys 'shareType' containing the name of a corresponding constant in \OCP\Share and
	 *                          'class' with the class name of the plugin
	 * @since 13.0.0
	 */
	public function registerPlugin(array $pluginInfo);
}
