<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WeatherStatus\Listeners;

use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/**
 * @template-implements IEventListener<BeforeTemplateRenderedEvent>
 */
class BeforeTemplateRenderedListener implements IEventListener {

	/**
	 * Inject our status widget script when the dashboard is loaded
	 * We need to do it like this because there is currently no PHP API for registering "status widgets"
	 */
	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent)) {
			return;
		}

		// Only handle the dashboard
		if ($event->getResponse()->getApp() !== 'dashboard') {
			return;
		}

		Util::addScript('weather_status', 'weather-status');
	}
}
