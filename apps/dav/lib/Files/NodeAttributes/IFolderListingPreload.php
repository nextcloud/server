<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Files\NodeAttributes;

use NCU\Files\NodeAttributes\INodeAttributeProvider;
use NCU\Files\NodeAttributes\NodeAttributeContext;
use OCP\Files\Folder;
use OCP\Files\Node;

/**
 * A provider that loads the attributes of a folder listing differently from the same nodes on their own
 */
interface IFolderListingPreload extends INodeAttributeProvider {
	/**
	 * Load the attributes of a folder and its direct children, a Depth 1 PROPFIND
	 *
	 * @param list<Node> $children
	 * @param list<string> $attributes
	 */
	public function preloadListing(NodeAttributeContext $context, Folder $folder, array $children, array $attributes): void;
}
