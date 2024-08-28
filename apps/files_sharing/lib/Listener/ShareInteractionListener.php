<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Listener;

use OCP\Contacts\Events\ContactInteractedWithEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserManager;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;
use function in_array;

/** @template-implements IEventListener<ShareCreatedEvent> */
class ShareInteractionListener implements IEventListener {
	private const SUPPORTED_SHARE_TYPES = [
		IShare::TYPE_USER,
		IShare::TYPE_EMAIL,
		IShare::TYPE_REMOTE,
	];

	public function __construct(
		private IEventDispatcher $dispatcher,
		private IUserManager $userManager,
		private LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof ShareCreatedEvent)) {
			// Unrelated
			return;
		}

		$share = $event->getShare();
		if (!in_array($share->getShareType(), self::SUPPORTED_SHARE_TYPES, true)) {
			$this->logger->debug('Share type does not allow to emit interaction event');
			return;
		}
		$actor = $this->userManager->get($share->getSharedBy());
		$sharedWith = $this->userManager->get($share->getSharedWith());
		if ($actor === null) {
			$this->logger->warning('Share was not created by a user, can\'t emit interaction event');
			return;
		}
		$interactionEvent = new ContactInteractedWithEvent($actor);
		switch ($share->getShareType()) {
			case IShare::TYPE_USER:
				$interactionEvent->setUid($share->getSharedWith());
				if ($sharedWith !== null) {
					$interactionEvent->setFederatedCloudId($sharedWith->getCloudId());
				}
				break;
			case IShare::TYPE_EMAIL:
				$interactionEvent->setEmail($share->getSharedWith());
				break;
			case IShare::TYPE_REMOTE:
				$interactionEvent->setFederatedCloudId($share->getSharedWith());
				break;
		}

		$this->dispatcher->dispatchTyped($interactionEvent);
	}
}
