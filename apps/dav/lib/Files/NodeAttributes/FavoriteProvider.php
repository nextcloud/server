<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Files\NodeAttributes;

use NCU\Files\NodeAttributes\INodeAttributeProvider;
use NCU\Files\NodeAttributes\NodeAttributeContext;
use OCA\DAV\Connector\Sabre\TagsPlugin;
use OCP\Files\Node;
use OCP\ITagManager;

/**
 * Whether the user marked a node as favorite, only for logged in users
 */
class FavoriteProvider implements INodeAttributeProvider {
	/** @var array<string, array<int, bool>> favorite state by user id and file id */
	private array $favorites = [];

	public function __construct(
		private readonly ITagManager $tagManager,
	) {
	}

	#[\Override]
	public function preload(NodeAttributeContext $context, array $nodes, array $attributes): void {
		$userId = $context->getUser()?->getUID();
		if ($userId === null) {
			return;
		}

		$fileIds = array_values(array_filter(
			array_map(static fn (Node $node): int => $node->getId(), $nodes),
			fn (int $fileId): bool => !isset($this->favorites[$userId][$fileId]),
		));
		if ($fileIds === []) {
			return;
		}

		$tags = $this->tagManager->load('files', [], false, $userId)?->getTagsForObjects($fileIds) ?: [];
		foreach ($fileIds as $fileId) {
			$this->favorites[$userId][$fileId] = in_array(TagsPlugin::TAG_FAVORITE, $tags[$fileId] ?? [], true);
		}
	}

	#[\Override]
	public function getAttributes(NodeAttributeContext $context, Node $node, array $attributes): array {
		$userId = $context->getUser()?->getUID();
		if ($userId === null) {
			return [];
		}

		$fileId = $node->getId();
		if (!isset($this->favorites[$userId][$fileId])) {
			$this->preload($context, [$node], $attributes);
		}

		return [
			TagsPlugin::FAVORITE_PROPERTYNAME => $this->favorites[$userId][$fileId] ? 1 : 0,
		];
	}
}
