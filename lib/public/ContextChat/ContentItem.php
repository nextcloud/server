<?php

/**
 * SPDX-FileCopyrightText: 2024-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\ContextChat;

/**
 * @since 32.0.0
 */
class ContentItem {
	/**
	 * @param string $itemId
	 * @param string $providerId
	 * @param string $title
	 * @param string $content
	 * @param string $documentType
	 * @param \DateTime $lastModified
	 * @param string[] $users
	 * @since 32.0.0
	 */
	public function __construct(
		public string $itemId,
		public string $providerId,
		public string $title,
		public string $content,
		public string $documentType,
		public \DateTime $lastModified,
		public array $users,
	) {
	}
}
