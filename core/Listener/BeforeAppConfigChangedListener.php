<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OCP\Config\BeforeAppConfigDeletedEvent;
use OCP\Config\BeforeAppConfigSetEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<BeforeAppConfigSetEvent|BeforeAppConfigDeletedEvent>
 */
class BeforeAppConfigChangedListener implements IEventListener {
	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof BeforeAppConfigSetEvent) {
			$value = $event->getConfigValue();
		} elseif ($event instanceof BeforeAppConfigDeletedEvent) {
			$value = null;
		} else {
			return;
		}

		$key = $event->getConfigKey();
		if (in_array($key, ['installed_version', 'enabled', 'types'], true)) {
			$event->setValid(false);
			return;
		}

		if ($event->getAppId() !== 'core') {
			return;
		}

		if (($key === 'encryption_enabled' && $value !== 'yes')
			|| str_starts_with($key, 'public_')
			|| str_starts_with($key, 'remote_')) {
			$event->setValid(false);
		}
	}
}
