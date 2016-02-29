<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OCP\Activity\v2;

interface IFilter {

	/**
	 * @return string App id that registers the filter
	 */
	public function getApp();

	/**
	 * @return string Unique identifier for your filter
	 */
	public function getId();

	/**
	 * @return string Translated display name
	 */
	public function getName();

	/**
	 * @return string Full image path to an icon
	 */
	public function getIcon();

	/**
	 * @return string[] Leave empty, when events from all apps should be returned
	 */
	public function filterApps();

	/**
	 * @param string[] $selectedTypes Types as per the user's preferences
	 * @return string[] Should respect $selectedTypes and only reduce the list.
	 *                  If empty, no events will be shown in the filter
	 */
	public function filterTypes(array $selectedTypes);

}
