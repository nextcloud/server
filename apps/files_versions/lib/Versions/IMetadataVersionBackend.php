<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	 * @param int $revision the key for the json value of the metadata column
	 * @param string $key the key for the json value of the metadata column
	 * @param string $value the value that corresponds to the key in the metadata column
	 * @since 29.0.0
	 */
	public function setMetadataValue(Node $node, int $revision, string $key, string $value): void;
}
