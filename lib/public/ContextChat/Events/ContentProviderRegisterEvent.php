<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\ContextChat\Events;

use OCP\ContextChat\IContentManager;
use OCP\ContextChat\IContentProvider;
use OCP\EventDispatcher\Event;

/**
 * @since 32.0.0
 */
class ContentProviderRegisterEvent extends Event {
	public function __construct(
		private IContentManager $contentManager,
	) {
	}

	/**
	 * @param string $appId
	 * @param string $providerId
	 * @param class-string<IContentProvider> $providerClass
	 * @return void
	 * @since 32.0.0
	 */
	public function registerContentProvider(string $appId, string $providerId, string $providerClass): void {
		$this->contentManager->registerContentProvider($appId, $providerId, $providerClass);
	}
}
