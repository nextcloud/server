<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Eduardo Morales <emoral435@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_Versions\Versions;

/**
 * This interface allows for just direct accessing of the metadata column JSON
 * @since 29.0.0
 */
interface IMetadataVersion {
	/**
	 * @abstract retrieves the metadata value from our $key param
	 *
	 * @param string $key the key for the json value of the metadata column
	 * @since 29.0.0
	 */
	public function getMetadataValue(string $key): string;
}
