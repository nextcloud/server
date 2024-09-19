<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Testing\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\Settings\Events\DeclarativeSettingsSetValueEvent;

/**
 * @template-implements IEventListener<DeclarativeSettingsSetValueEvent>
 */
class SetDeclarativeSettingsValueListener implements IEventListener {

	public function __construct(
		private IConfig $config,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof DeclarativeSettingsSetValueEvent) {
			return;
		}

		if ($event->getApp() !== 'testing') {
			return;
		}

		error_log('Testing app wants to store ' . $event->getValue() . ' for field ' . $event->getFieldId() . ' for user ' . $event->getUser()->getUID());
		$this->config->setUserValue($event->getUser()->getUID(), $event->getApp(), $event->getFieldId(), $event->getValue());
	}
}
