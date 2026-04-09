<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\FullTextSearch\Model;

/**
 * Interface IIndexOptions
 *
 * IndexOptions are created in FullTextSearch when an admin initiate an index
 * from the command line:
 *
 * ./occ fulltextsearch:index "{\"option1\": \"value\", \"option2\": true}"
 *
 * @since 15.0.0
 *
 */
interface IIndexOptions {
	/**
	 * Get the value (as a string) for an option.
	 *
	 * @since 15.0.0
	 *
	 * @param string $option
	 * @param string $default
	 *
	 * @return string
	 */
	public function getOption(string $option, string $default = ''): string;

	/**
	 * Get the value (as an array) for an option.
	 *
	 * @since 15.0.0
	 *
	 * @param string $option
	 * @param array $default
	 *
	 * @return array
	 */
	public function getOptionArray(string $option, array $default = []): array;

	/**
	 * Get the value (as an boolean) for an option.
	 *
	 * @since 15.0.0
	 *
	 * @param string $option
	 * @param bool $default
	 *
	 * @return bool
	 */
	public function getOptionBool(string $option, bool $default): bool;
}
