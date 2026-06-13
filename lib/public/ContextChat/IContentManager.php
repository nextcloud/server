<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\ContextChat;

/**
 * @since 32.0.0
 */
interface IContentManager {
	/**
	 * Checks if the context chat app is enabled or not
	 *
	 * @return bool
	 * @since 32.0.0
	 */
	public function isContextChatAvailable(): bool;

	/**
	 * @param string $appId
	 * @param string $providerId
	 * @param class-string<IContentProvider> $providerClass
	 * @return void
	 * @since 32.0.0
	 */
	public function registerContentProvider(string $appId, string $providerId, string $providerClass): void;

	/**
	 * Emits an event to collect all content providers
	 *
	 * @return void
	 * @since 32.0.0
	 */
	public function collectAllContentProviders(): void;

	/**
	 * Providers can use this to submit content for indexing in context chat
	 *
	 * @param string $appId
	 * @param ContentItem[] $items
	 * @return void
	 * @since 32.0.0
	 */
	public function submitContent(string $appId, array $items): void;

	/**
	 * Update access for a content item for specified users.
	 * This modifies the access list for the content item,
	 * 	allowing or denying access to the specified users.
	 * If no user has access to the content item, it will be removed from the knowledge base.
	 *
	 * @param string $appId
	 * @param string $providerId
	 * @param string $itemId
	 * @param Type\UpdateAccessOp::* $op
	 * @param array $userIds
	 * @return void
	 * @since 32.0.0
	 */
	public function updateAccess(string $appId, string $providerId, string $itemId, string $op, array $userIds): void;

	/**
	 * Update access for content items from the given provider for specified users.
	 * If no user has access to the content item, it will be removed from the knowledge base.
	 *
	 * @param string $appId
	 * @param string $providerId
	 * @param Type\UpdateAccessOp::* $op
	 * @param array $userIds
	 * @return void
	 * @since 32.0.0
	 */
	public function updateAccessProvider(string $appId, string $providerId, string $op, array $userIds): void;

	/**
	 * Update access for a content item for specified users declaratively.
	 * This overwrites the access list for the content item,
	 * 	allowing only the specified users access to it.
	 *
	 * @param string $appId
	 * @param string $providerId
	 * @param string $itemId
	 * @param array $userIds
	 * @return void
	 * @since 32.0.0
	 */
	public function updateAccessDeclarative(string $appId, string $providerId, string $itemId, array $userIds): void;

	/**
	 * Delete all content items and access lists for a provider.
	 * This does not unregister the provider itself.
	 *
	 * @param string $appId
	 * @param string $providerId
	 * @return void
	 * @since 32.0.0
	 */
	public function deleteProvider(string $appId, string $providerId): void;

	/**
	 * Remove a content item from the knowledge base of context chat.
	 *
	 * @param string $appId
	 * @param string $providerId
	 * @param string[] $itemIds
	 * @return void
	 * @since 32.0.0
	 */
	public function deleteContent(string $appId, string $providerId, array $itemIds): void;
}
