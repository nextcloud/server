<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Listener;

use OCA\Files\AppInfo\Application;
use OCA\Files\Service\SettingsService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Settings\Events\DeclarativeSettingsSetValueEvent;

/** @template-implements IEventListener<DeclarativeSettingsSetValueEvent> */
class DeclarativeSettingsSetValueEventListener implements IEventListener {

	public function __construct(
		private SettingsService $service,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof DeclarativeSettingsSetValueEvent)) {
			return;
		}

		if ($event->getApp() !== Application::APP_ID) {
			return;
		}

		switch ($event->getFieldId()) {
			case 'windows_support':
				$this->service->setFilesWindowsSupport((bool) $event->getValue());
				$event->stopPropagation();
				break;
		}
	}
}
