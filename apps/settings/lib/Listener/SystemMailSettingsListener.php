<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Listener;

use OCA\Settings\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;
use OCP\Settings\Events\DeclarativeSettingsGetValueEvent;
use OCP\Settings\Events\DeclarativeSettingsSetValueEvent;

/** @template-implements IEventListener<DeclarativeSettingsGetValueEvent|DeclarativeSettingsSetValueEvent> */
class SystemMailSettingsListener implements IEventListener {

	public function __construct(
		private IAppConfig $config,
	) {
	}

	public function handle(Event $event): void {

		/** @var DeclarativeSettingsGetValueEvent|DeclarativeSettingsSetValueEvent $event */
		if ($event->getApp() !== Application::APP_ID) {
			return;
		}

		if ($event instanceof DeclarativeSettingsGetValueEvent) {
			$this->handleGetValue($event);
			return;
		}

		if ($event instanceof DeclarativeSettingsSetValueEvent) {
			$this->handleSetValue($event);
			return;
		}
		
	}

	private function handleGetValue(DeclarativeSettingsGetValueEvent $event): void {
		
		$event->setValue(
			match($event->getFieldId()) {
				'mail_providers_disabled' => $this->config->getValueInt('core', 'mail_providers_disabled', 0),
			}
		);

	}

	private function handleSetValue(DeclarativeSettingsSetValueEvent $event): void {

		switch ($event->getFieldId()) {
			case 'mail_providers_disabled':
				$this->config->setValueInt('core', 'mail_providers_disabled', (int)$event->getValue());
				$event->stopPropagation();
				break;
		}

	}

}
