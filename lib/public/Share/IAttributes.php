<?php
/**
 * SPDX-FileCopyrightText: 2019 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
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
