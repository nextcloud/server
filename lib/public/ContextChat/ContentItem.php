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
		/**
		 * @since 32.0.0
		 */
		public string $itemId,
		/**
		 * @since 32.0.0
		 */
		public string $providerId,
		/**
		 * @since 32.0.0
		 */
		public string $title,
		/**
		 * @since 32.0.0
		 */
		public string $content,
		/**
		 * @since 32.0.0
		 */
		public string $documentType,
		/**
		 * @since 32.0.0
		 */
		public \DateTime $lastModified,
		/**
		 * @since 32.0.0
		 */
		public array $users,
	) {
	}
}
