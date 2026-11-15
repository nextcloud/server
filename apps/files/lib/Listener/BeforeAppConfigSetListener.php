<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Listener;

use OCP\Config\BeforeAppConfigSetEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;

/**
 * @template-implements IEventListener<BeforeAppConfigSetEvent>
 */
class BeforeAppConfigSetListener implements IEventListener {
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof BeforeAppConfigSetEvent) {
			return;
		}

		if ($event->getAppId() === 'files'
			&& $event->getConfigKey() === 'default_quota'
			&& $event->getConfigValue() === 'none'
			&& $this->appConfig->getValueInt('files', 'allow_unlimited_quota', 1) === 0) {
			$event->setValid(false);
		}
	}
}
