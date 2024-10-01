<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Listener;

use OCA\Files\AppInfo\Application;
use OCA\Files\Service\SettingsService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Settings\Events\DeclarativeSettingsGetValueEvent;

/** @template-implements IEventListener<DeclarativeSettingsGetValueEvent> */
class DeclarativeSettingsGetValueEventListener implements IEventListener {

	public function __construct(
		private SettingsService $service,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof DeclarativeSettingsGetValueEvent)) {
			return;
		}

		if ($event->getApp() !== Application::APP_ID) {
			return;
		}

		$event->setValue(
			match($event->getFieldId()) {
				'windows_support' => $this->service->hasFilesWindowsSupport(),
			}
		);
	}
}
