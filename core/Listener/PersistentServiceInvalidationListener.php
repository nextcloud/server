<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\AppFramework\Utility\IPersistentServiceInvalidator;
use OCP\AppFramework\Utility\PersistentServiceGroup;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<AppEnableEvent|AppDisableEvent>
 */
class PersistentServiceInvalidationListener implements IEventListener {
	public function __construct(
		private IPersistentServiceInvalidator $invalidator,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof AppEnableEvent || $event instanceof AppDisableEvent) {
			$this->invalidator->invalidate(PersistentServiceGroup::Apps);
		}
	}
}
