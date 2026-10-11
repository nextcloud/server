<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Files\NodeAttributes;

use OCP\Files\Node;

/**
 * Provides attributes of files and folders, the same way to WebDAV and to PHP.
 *
 * Register it with `IRegistrationContext::registerNodeAttributeProvider()`
 * along with the attributes it provides, in Clark notation like the WebDAV
 * properties, e.g. `{http://nextcloud.org/ns}has-preview`.
 *
 * `preload()` is called once with all the nodes of a response, `getAttributes()`
 * then once per node. Load everything needed in `preload()`: `getAttributes()`
 * must not query per node.
 *
 * @experimental 36.0.0
 */
interface INodeAttributeProvider {
	/**
	 * Load what is needed to provide the attributes of all these nodes at once
	 *
	 * @param list<Node> $nodes
	 * @param list<string> $attributes the requested attributes of this provider, in Clark notation
	 * @experimental 36.0.0
	 */
	public function preload(NodeAttributeContext $context, array $nodes, array $attributes): void;

	/**
	 * Get the requested attributes of a node.
	 * Attributes that do not apply to this node or context are left out.
	 *
	 * @param list<string> $attributes the requested attributes of this provider, in Clark notation
	 * @return array<string, bool|int|float|string|NodeAttributeList|null> values by attribute in Clark notation
	 * @experimental 36.0.0
	 */
	public function getAttributes(NodeAttributeContext $context, Node $node, array $attributes): array;
}
