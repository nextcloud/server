<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Log\Audit\CriticalActionPerformedEvent;

/** @template-implements IEventListener<CriticalActionPerformedEvent> */
class CriticalActionPerformedEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if (!($event instanceof CriticalActionPerformedEvent)) {
			return;
		}

		$this->log(
			$event->getLogMessage(),
			$event->getParameters(),
			array_keys($event->getParameters()),
			$event->getObfuscateParameters()
		);
	}
}
