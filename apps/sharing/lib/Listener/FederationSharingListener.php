<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sharing\Listener;

use NCU\Sharing\Event\ShareRecipientAddedEvent;
use NCU\Sharing\Event\ShareStateUpdatedEvent;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\ShareState;
use OCA\Sharing\Federation\FederationSharingService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Sends shares to remote recipients' OCM /shares endpoint
 *
 * @template-implements IEventListener<ShareStateUpdatedEvent|ShareRecipientAddedEvent>
 */
final readonly class FederationSharingListener implements IEventListener {
	public function __construct(
		private FederationSharingService $federationSharingService,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof ShareStateUpdatedEvent) {
			$share = $event->getShare();
			if ($share->state !== ShareState::Active || $event->getPreviousState() === ShareState::Active) {
				return;
			}

			$remoteRecipients = array_filter($share->recipients, static fn (ShareRecipient $recipient): bool => $recipient->instance !== null);
			foreach ($remoteRecipients as $recipient) {
				$this->federationSharingService->sendShare($share, $recipient);
			}
		}

		if ($event instanceof ShareRecipientAddedEvent) {
			$share = $event->getShare();
			$recipient = $event->getRecipient();
			if ($share->state !== ShareState::Active || $recipient->instance === null) {
				return;
			}

			$this->federationSharingService->sendShare($share, $recipient);
		}
	}
}
