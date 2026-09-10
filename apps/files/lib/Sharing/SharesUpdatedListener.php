<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing;

use NCU\Sharing\Event\SharesUpdatedEvent;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\ShareState;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use Override;
use RuntimeException;

/**
 * @implements IEventListener<SharesUpdatedEvent>
 */
final readonly class SharesUpdatedListener implements IEventListener {
	public function __construct(
		private ISharingRegistry $registry,
		private SourceNodeTargetManager $sourceNodeTargetManager,
		private IRootFolder $rootFolder,
	) {
	}

	#[Override]
	public function handle(Event $event): void {
		foreach ($event->getShares() as $share) {
			if ($share->state !== ShareState::Active) {
				continue;
			}

			$ownerUserFolder = $this->rootFolder->getUserFolder($share->owner->userId);

			foreach ($share->sources as $source) {
				if ($source->class !== NodeShareSourceType::class) {
					continue;
				}

				$sourceNode = $ownerUserFolder->getFirstNodeById((int)$source->value);
				if (!$sourceNode instanceof Node) {
					throw new NotFoundException();
				}

				/** @var array<int|string, true> */
				$userIds = [];
				foreach ($share->recipients as $recipient) {
					if ($recipient->instance !== null) {
						continue;
					}

					if (($recipientType = $this->registry->getRecipientTypes()[$recipient->class] ?? null) === null) {
						throw new RuntimeException('The recipient type is not registered: ' . $recipient->class);
					}

					foreach ($recipientType->getUsers($recipient->value) as $user) {
						$userIds[$user->getUID()] = true;
					}
				}

				// Casting is needed, because using the user ID as the array key could convert it to an integer if it is numeric.
				$this->sourceNodeTargetManager->getTargets(array_map(static fn (int|string $userId): string => (string)$userId, array_keys($userIds)), $share->owner, $sourceNode);
			}
		}
	}
}
