<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Files\NodeAttributes;

use OCP\Files\Node;

/**
 * Serialize nodes to the data the `@nextcloud/files` library builds its nodes from,
 * with the attributes of the registered node attribute providers.
 *
 * @psalm-type NodeData = array{
 *     id: int,
 *     source: string,
 *     root: string,
 *     owner: string|null,
 *     mime: string,
 *     mtime: int,
 *     crtime: int|null,
 *     size: int|float,
 *     permissions: int,
 *     displayname: string,
 *     type: 'file'|'folder',
 *     attributes: array<string, mixed>,
 * }
 * @experimental 36.0.0
 */
interface INodeSerializer {
	/**
	 * @param list<Node> $nodes nodes as seen by the context: from the user folder, or within the public share
	 * @param list<string>|null $attributes attributes to include in Clark notation, null for all registered ones
	 * @return list<NodeData>
	 * @experimental 36.0.0
	 */
	public function serialize(NodeAttributeContext $context, array $nodes, ?array $attributes = null): array;

	/**
	 * Get the attributes the registered providers offer, in Clark notation
	 *
	 * @return list<string>
	 * @experimental 36.0.0
	 */
	public function getAttributeNames(): array;
}
