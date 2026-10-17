<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCA\AdminAudit\Operation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Federation\Events\TrustedServerRemovedEvent;
use Override;

/**
 * @template-implements IEventListener<TrustedServerRemovedEvent>
 */
class FederationEventListener extends Action implements IEventListener {
	#[Override]
	public function handle(Event $event): void {
		if ($event instanceof TrustedServerRemovedEvent) {
			$this->trustedServerRemoved($event);
		}
	}

	private function trustedServerRemoved(TrustedServerRemovedEvent $event): void {
		$this->log(Operation::FederationTrustedServerRemoved, 'Trusted server "%s" was removed',
			[
				'url' => $event->getUrl(),
				'urlHash' => $event->getUrlHash(),
			],
			['url', 'urlHash']
		);
	}
}
