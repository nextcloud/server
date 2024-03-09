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

use OCP\Files\Node;

/**
 * This interface edits the metadata column of a node.
 * Each column of the metadata has a key => value mapping.
 * @since 29.0.0
 */
interface IMetadataVersionBackend {
	/**
	 * Sets a key value pair in the metadata column corresponding to the node's version.
	 *
	 * @param Node $node the node that triggered the Metadata event listener, aka, the file version
	 * @param string $key the key for the json value of the metadata column
	 * @param string $value the value that corresponds to the key in the metadata column
	 * @since 29.0.0
	 */
	public function setMetadataValue(Node $node, string $key, string $value): void;

	/**
	 * Retrieves a corresponding value from the metadata column using the key.
	 *
	 * @param Node $node the node that triggered the Metadata event listener, aka, the file version
	 * @param string $key the key for the json value of the metadata column
	 * @since 29.0.0
	 */
	public function getMetadataValue(Node $node, string $key): ?string;
}
