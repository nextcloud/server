<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Listener;

use OCA\Files_External\Service\DBConfigService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;

/** @template-implements IEventListener<GroupDeletedEvent> */
class GroupDeletedListener implements IEventListener {
	public function __construct(
		private DBConfigService $config,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof GroupDeletedEvent) {
			return;
		}
		$this->config->modifyMountsOnGroupDelete($event->getGroup()->getGID());
	}
}
