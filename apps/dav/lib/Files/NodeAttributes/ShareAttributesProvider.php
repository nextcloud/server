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
use OCP\Files\NotFoundException;
use OCP\Files\Storage\ISharedStorage;
use OCP\Share\IAttributes;

/**
 * Attributes of the share a node was received through, like the download permission.
 * Read from the share of the storage, no query.
 */
class ShareAttributesProvider implements INodeAttributeProvider {
	#[\Override]
	public function preload(NodeAttributeContext $context, array $nodes, array $attributes): void {
	}

	/**
	 * The value is the JSON encoded list of attributes, as WebDAV has always sent it
	 */
	#[\Override]
	public function getAttributes(NodeAttributeContext $context, Node $node, array $attributes): array {
		return [
			FilesPlugin::SHARE_ATTRIBUTES_PROPERTYNAME => json_encode($this->getShareAttributes($node), JSON_THROW_ON_ERROR),
		];
	}

	private function getShareAttributes(Node $node): array {
		try {
			$storage = $node->getStorage();
		} catch (NotFoundException) {
			return [];
		}

		if (!$storage->instanceOfStorage(ISharedStorage::class)) {
			return [];
		}

		/** @var ISharedStorage $storage */
		$shareAttributes = $storage->getShare()->getAttributes();
		return $shareAttributes instanceof IAttributes ? $shareAttributes->toArray() : [];
	}
}
