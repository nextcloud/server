<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Files\NodeAttributes;

use OCP\Files\Node;

/**
 * The attributes of the registered node attribute providers,
 * for code serving them in its own format, like the WebDAV server.
 *
 * A provider that fails is logged and skipped, the other attributes are still served.
 *
 * @experimental 36.0.0
 */
interface INodeAttributeRegistry {
	/**
	 * Get the registered attributes in Clark notation
	 *
	 * @return list<string>
	 * @experimental 36.0.0
	 */
	public function getAttributeNames(): array;

	/**
	 * Preload the requested attributes of these nodes, nodes already preloaded for this context are skipped
	 *
	 * @param list<Node> $nodes
	 * @param list<string> $attributes in Clark notation
	 * @experimental 36.0.0
	 */
	public function preload(NodeAttributeContext $context, array $nodes, array $attributes): void;

	/**
	 * Get the requested attributes of a node, attributes that do not apply are left out
	 *
	 * @param list<string> $attributes in Clark notation
	 * @return array<string, bool|int|float|string|NodeAttributeList|null> values by attribute in Clark notation
	 * @experimental 36.0.0
	 */
	public function getAttributes(NodeAttributeContext $context, Node $node, array $attributes): array;
}
