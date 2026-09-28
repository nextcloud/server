<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UpdateNotification\Listener;

use OCA\UpdateNotification\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;
use OCP\Util;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<BeforeTemplateRenderedEvent> */
class BeforeTemplateRenderedEventListener implements IEventListener {

	public function __construct(
		private IAppManager $appManager,
		private LoggerInterface $logger,
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * @param BeforeTemplateRenderedEvent $event
	 */
	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent)) {
			return;
		}

		if (!$this->appConfig->getValueBool(Application::APP_NAME, 'app_updated.enabled', true)) {
			return;
		}

		// Only handle logged in users
		if (!$event->isLoggedIn()) {
			return;
		}

		// Ignore when notifications are disabled
		if (!$this->appManager->isEnabledForUser('notifications')) {
			return;
		}

		Util::addInitScript(Application::APP_NAME, 'init');
	}
}
