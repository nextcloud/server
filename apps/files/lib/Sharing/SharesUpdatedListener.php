<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing;

use NCU\Sharing\Event\SharesUpdatedEvent;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\NotFoundException;
use Override;
use RuntimeException;

/**
 * @implements IEventListener<SharesUpdatedEvent>
 */
final readonly class SharesUpdatedListener implements IEventListener {
	public function __construct(
		private ISharingManager $manager,
		private ISharingRegistry $registry,
		private SourceNodeTargetManager $sourceNodeTargetManager,
	) {
	}

	#[Override]
	public function handle(Event $event): void {
		foreach ($event->shareIds as $shareId) {
			$share = $this->manager->getShare(new ShareAccessContext(overrideChecks: true), $shareId);
			if ($share->state !== ShareState::Active) {
				continue;
			}

			foreach ($share->sources as $source) {
				if ($source->class !== NodeShareSourceType::class) {
					continue;
				}

				$userIds = [];
				foreach ($share->recipients as $recipient) {
					if ($recipient->instance !== null) {
						continue;
					}

					if (($recipientType = $this->registry->getRecipientTypes()[$recipient->class] ?? null) === null) {
						throw new RuntimeException('The recipient type is not registered: ' . $recipient->class);
					}

					foreach ($recipientType->getUsers($recipient->value) as $userId) {
						$userIds[$userId] = true;
					}
				}

				foreach (array_keys($userIds) as $userId) {
					try {
						$this->sourceNodeTargetManager->createDefaultTarget($userId, $share->owner, (int)$source->value);
					} catch (NotFoundException) {
						// The same exception will happen for the other users, because the source node is the same, so we can just stop here.
						break;
					}
				}
			}
		}
	}
}
