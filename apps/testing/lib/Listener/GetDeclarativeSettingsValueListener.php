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
use OCP\Settings\Events\DeclarativeSettingsGetValueEvent;

/**
 * @template-implements IEventListener<DeclarativeSettingsGetValueEvent>
 */
class GetDeclarativeSettingsValueListener implements IEventListener {

	public function __construct(
		private IConfig $config,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof DeclarativeSettingsGetValueEvent) {
			return;
		}

		if ($event->getApp() !== 'testing') {
			return;
		}

		$value = $this->config->getUserValue($event->getUser()->getUID(), $event->getApp(), $event->getFieldId());
		$event->setValue($value);
	}
}
