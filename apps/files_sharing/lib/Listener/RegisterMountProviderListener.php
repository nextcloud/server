<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\External\MountProvider as ExternalMountProvider;
use OCA\Files_Sharing\MountProvider;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\RegisterMountProviderEvent;

/** @template-implements IEventListener<RegisterMountProviderEvent> */
class RegisterMountProviderListener implements IEventListener {

	public function __construct(
		private MountProvider $mountProvider,
		private ExternalMountProvider $externalMountProvider,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof RegisterMountProviderEvent)) {
			return;
		}

		$mountProviderCollection = $event->getProviderCollection();
		$mountProviderCollection->registerProvider($this->mountProvider);
		$mountProviderCollection->registerProvider($this->externalMountProvider);
	}
}
