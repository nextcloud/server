<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Files\NodeAttributes;

use NCU\Files\NodeAttributes\INodeAttributeProvider;
use NCU\Files\NodeAttributes\NodeAttributeContext;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCP\Files\Node;
use OCP\IPreview;

/**
 * Whether a preview can be generated for a node
 */
class HasPreviewProvider implements INodeAttributeProvider {
	public function __construct(
		private readonly IPreview $previewManager,
	) {
	}

	#[\Override]
	public function preload(NodeAttributeContext $context, array $nodes, array $attributes): void {
		// Previews availability only depends on the node itself
	}

	#[\Override]
	public function getAttributes(NodeAttributeContext $context, Node $node, array $attributes): array {
		return [
			FilesPlugin::HAS_PREVIEW_PROPERTYNAME => $this->previewManager->isAvailable($node),
		];
	}
}
