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
	 * Sets an attribute. If the key did not exist before it will be created.
	 *
	 * @param string $scope scope
	 * @param string $key key
	 * @param bool|string|array|null $value value
	 * @return IAttributes The modified object
	 * @since 25.0.0
	 */
	public function setAttribute(string $scope, string $key, mixed $value): IAttributes;

	/**
	 * Returns the attribute for given scope id and key.
	 * If attribute does not exist, returns null
	 *
	 * @param string $scope scope
	 * @param string $key key
	 * @return bool|string|array|null
	 * @since 25.0.0
	 */
	public function getAttribute(string $scope, string $key): mixed;

	/**
	 * Formats the IAttributes object to array with the following format:
	 * [
	 * 	0 => [
	 * 			"scope" => <string>,
	 * 			"key" => <string>,
	 * 			"value" => <bool|string|array|null>,
	 * 		],
	 * 	...
	 * ]
	 *
	 * @return array formatted IAttributes
	 * @since 25.0.0
	 * @since 30.0.0, `enabled` was renamed to `value`
	 */
	public function toArray(): array;
}
