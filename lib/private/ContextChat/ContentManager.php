<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\ContextChat;

use OCA\ContextChat\Public\ContentManager as ContextChatContentManager;
use OCP\ContextChat\IContentManager;

class ContentManager implements IContentManager {
	public function __construct(
		private ?ContextChatContentManager $contentManager,
	) {
	}

	public function isContextChatAvailable(): bool {
		return $this->contentManager !== null;
	}

	public function registerContentProvider(string $appId, string $providerId, string $providerClass): void {
		$this->contentManager?->registerContentProvider($appId, $providerId, $providerClass);
	}

	public function collectAllContentProviders(): void {
		$this->contentManager?->collectAllContentProviders();
	}

	public function submitContent(string $appId, array $items): void {
		$this->contentManager?->submitContent($appId, $items);
	}

	public function updateAccess(string $appId, string $providerId, string $itemId, string $op, array $userIds): void {
		$this->contentManager?->updateAccess($appId, $providerId, $itemId, $op, $userIds);
	}

	public function updateAccessProvider(string $appId, string $providerId, string $op, array $userIds): void {
		$this->contentManager?->updateAccessProvider($appId, $providerId, $op, $userIds);
	}

	public function updateAccessDeclarative(string $appId, string $providerId, string $itemId, array $userIds): void {
		$this->contentManager?->updateAccessDeclarative($appId, $providerId, $itemId, $op, $userIds);
	}

	public function deleteProvider(string $appId, string $providerId): void {
		$this->contentManager?->deleteProvider($appId, $providerId);
	}

	public function deleteContent(string $appId, string $providerId, array $itemIds): void {
		$this->contentManager?->deleteContent($appId, $providerId, $itemIds);
	}
}
