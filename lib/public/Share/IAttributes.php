<?php
/**
 * @author Piotr Mrowczynski <piotr@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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
namespace OCP\Share;

/**
 * Interface IAttributes
 *
 * @package OCP\Share
 * @since 25.0.0
 */
interface IAttributes {
	/**
	 * Sets an attribute enabled/disabled. If the key did not exist before it will be created.
	 *
	 * @param string $scope scope
	 * @param string $key key
	 * @param bool $enabled enabled
	 * @return IAttributes The modified object
	 * @since 25.0.0
	 */
	public function setAttribute($scope, $key, $enabled);

	/**
	 * Returns if attribute is enabled/disabled for given scope id and key.
	 * If attribute does not exist, returns null
	 *
	 * @param string $scope scope
	 * @param string $key key
	 * @return bool|null
	 * @since 25.0.0
	 */
	public function getAttribute($scope, $key);

	/**
	 * Formats the IAttributes object to array with the following format:
	 * [
	 * 	0 => [
	 * 			"scope" => <string>,
	 * 			"key" => <string>,
	 * 			"enabled" => <bool>
	 * 		],
	 * 	...
	 * ]
	 *
	 * @return array formatted IAttributes
	 * @since 25.0.0
	 */
	public function toArray();
}
